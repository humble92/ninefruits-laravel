<?php

namespace App\Domain\Person\Actions;

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Grading\TermRecord;
use App\Models\Person\StudentRecord;
use App\Models\Person\YearLevelChange;
use App\Models\Person\YearLevelRule;
use App\Models\Person\YearLevelRun;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageYearLevels
{
    public function __construct(private readonly AuditLogger $audit, private readonly AuthorizationService $authorization) {}

    public function canManage(?User $user): bool
    {
        return $user !== null && $this->authorization->scopeFor($user, Permission::StudentRecordsManage)->isGlobal;
    }

    public function canConfigure(?User $user): bool
    {
        return $user !== null && $this->authorization->scopeFor($user, Permission::InstitutionSettingsManage)->isGlobal;
    }

    /** @param array<string, mixed> $data */
    public function saveRule(?YearLevelRule $rule, array $data, User $staff): YearLevelRule
    {
        abort_unless($this->canConfigure($staff), 403);
        $basis = $data['basis'];
        $thresholds = $basis === 'manual' ? [] : array_values($data['thresholds'] ?? []);
        $previous = 0.0;
        foreach ($thresholds as $row) {
            $minimum = $row['minimum'];
            if (! is_numeric($minimum) || $minimum <= $previous || $minimum > 99999
                || ($basis === 'terms' && (float) $minimum !== floor((float) $minimum))
                || abs((float) $minimum * 10 - round((float) $minimum * 10)) > 0.000001) {
                $this->invalid('invalid_thresholds');
            }
            $previous = (float) $minimum;
        }

        if (! in_array($basis, ['credits', 'terms', 'manual'], true)
            || ($basis !== 'manual' && (count($thresholds) < 1 || count($thresholds) > 9))) {
            $this->invalid('invalid_thresholds');
        }

        return DB::transaction(function () use ($rule, $data, $thresholds, $staff): YearLevelRule {
            // Serialize default changes even when there are no rule rows yet.
            DB::select('SELECT pg_advisory_xact_lock(?)', [2190101]);
            $rule ??= new YearLevelRule;
            if ($data['is_default']) {
                YearLevelRule::query()->where('is_default', true)
                    ->when($rule->exists, fn ($query) => $query->whereKeyNot($rule->id))
                    ->update(['is_default' => false]);
            }
            $rule->fill([...$data, 'thresholds' => $thresholds])->save();
            $this->audit->record('year_level.rule_saved', $rule, 'Configure year-level rules', causer: $staff);

            return $rule;
        });
    }

    public function preview(int $termId, CarbonImmutable $cutoff, User $staff, ?int $programId = null): YearLevelRun
    {
        abort_unless($this->canManage($staff), 403);

        return DB::transaction(function () use ($termId, $cutoff, $staff, $programId): YearLevelRun {
            $term = Term::query()->lockForUpdate()->findOrFail($termId);
            if ($cutoff->toDateString() >= $term->start_date->toDateString() || $cutoff->toDateString() > today(app(InstitutionSettings::class)->timezone)->toDateString()) {
                $this->invalid('invalid_cutoff');
            }
            if (YearLevelRun::query()->where('term_id', $termId)->whereIn('status', ['draft', 'scheduled'])->exists()) {
                $this->invalid('duplicate_run');
            }
            $rules = YearLevelRule::query()->get()->keyBy('id');
            $default = $rules->firstWhere('is_default', true);
            if ($default === null) {
                $this->invalid('missing_default');
            }
            $run = YearLevelRun::query()->create(['term_id' => $termId, 'cutoff_date' => $cutoff, 'program_id' => $programId]);
            StudentRecord::query()->with('program')
                ->when($programId !== null, fn ($query) => $query->where('program_id', $programId))
                ->where('status', StudentStatus::Enrolled)
                ->whereDate('admission_date', '<=', $cutoff)
                ->chunkById(500, function ($records) use ($cutoff, $run, $rules, $default): void {
                    // The latest closed cumulative result already accounts for retakes.
                    $totals = TermRecord::query()->join('terms', 'terms.id', '=', 'term_records.term_id')
                        ->whereIn('term_records.student_record_id', $records->modelKeys())
                        ->whereNotNull('term_records.closed_at')
                        ->where('terms.end_date', '<=', $cutoff->toDateString())
                        ->selectRaw(<<<'SQL'
                            DISTINCT ON (term_records.student_record_id)
                            term_records.student_record_id, term_records.cumulative_earned_credits AS credits,
                            COUNT(*) FILTER (WHERE term_records.earned_credits > 0)
                                OVER (PARTITION BY term_records.student_record_id) AS terms
                            SQL)
                        ->orderBy('term_records.student_record_id')->orderByDesc('terms.start_date')
                        ->orderByDesc('term_records.term_id')->toBase()->get()->keyBy('student_record_id');
                    $changes = [];
                    foreach ($records as $record) {
                        $rule = $rules->get($record->program->year_level_rule_id ?? 0) ?? $default;
                        $total = $totals->get($record->id);
                        if ($rule->basis === 'manual' || $total === null) {
                            continue;
                        }
                        $value = $rule->basis === 'credits' ? $total->credits : $total->terms;
                        $target = 1;
                        foreach ($rule->thresholds as $threshold) {
                            if ($value < $threshold['minimum']) {
                                break;
                            }
                            $target++;
                        }
                        if ($target > $record->year_level) {
                            $changes[] = [
                                'year_level_run_id' => $run->id,
                                'student_record_id' => $record->id,
                                'program_id' => $record->program_id,
                                'from_level' => $record->year_level,
                                'to_level' => $target,
                                'rule_name' => $rule->name,
                                'basis' => $rule->basis,
                                'measured_value' => $value,
                            ];
                        }
                    }
                    YearLevelChange::query()->insert($changes);
                });
            $this->audit->record('year_level.previewed', $run, 'Calculate proposed year levels', causer: $staff);

            return $run;
        });
    }

    public function schedule(int $id, CarbonImmutable $when, User $staff): void
    {
        abort_unless($this->canManage($staff), 403);
        DB::transaction(function () use ($id, $when, $staff): void {
            $run = YearLevelRun::query()->lockForUpdate()->findOrFail($id);
            if ($run->status !== 'draft' || $when->isPast() || ! $run->changes()->whereNull('outcome')->exists()) {
                $this->invalid('cannot_schedule');
            }
            $run->update(['status' => 'scheduled', 'scheduled_at' => $when, 'approved_by_id' => $staff->id]);
            $this->audit->record('year_level.scheduled', $run, 'Approve the reviewed year-level changes', causer: $staff);
        });
    }

    public function exclude(int $id, int $changeId, User $staff): void
    {
        abort_unless($this->canManage($staff), 403);
        DB::transaction(function () use ($id, $changeId, $staff): void {
            $run = YearLevelRun::query()->lockForUpdate()->findOrFail($id);
            if ($run->status !== 'draft') {
                $this->invalid('not_draft');
            }
            $change = $run->changes()->findOrFail($changeId);
            $change->update(['outcome' => $change->outcome === null ? 'excluded' : null]);
            $this->audit->record('year_level.reviewed', $run, 'Review a proposed year-level change', ['change_id' => $changeId, 'outcome' => $change->outcome], $staff);
        });
    }

    public function cancel(int $id, User $staff): void
    {
        abort_unless($this->canManage($staff), 403);
        DB::transaction(function () use ($id, $staff): void {
            $run = YearLevelRun::query()->lockForUpdate()->findOrFail($id);
            if (! in_array($run->status, ['draft', 'scheduled'], true)) {
                $this->invalid('cannot_cancel');
            }
            $run->update(['status' => 'canceled']);
            $this->audit->record('year_level.canceled', $run, 'Cancel the proposed or scheduled changes', causer: $staff);
        });
    }

    public function applyDue(): int
    {
        $count = 0;
        YearLevelRun::query()->where('status', 'scheduled')->where('scheduled_at', '<=', now())
            ->chunkById(100, function ($runs) use (&$count): void {
                foreach ($runs as $run) {
                    $count += DB::transaction(fn () => (int) $this->apply($run->id), 3);
                }
            });

        return $count;
    }

    private function apply(int $id): bool
    {
        $run = YearLevelRun::query()->lockForUpdate()->findOrFail($id);
        if ($run->status !== 'scheduled' || $run->scheduled_at === null || $run->scheduled_at->isFuture()) {
            return false;
        }
        $counts = ['applied' => 0, 'stale' => 0];
        $run->changes()->whereNull('outcome')->eachById(function (YearLevelChange $change) use (&$counts): void {
            $updated = StudentRecord::query()->whereKey($change->student_record_id)
                ->where('status', StudentStatus::Enrolled)->where('program_id', $change->program_id)
                ->where('year_level', $change->from_level)->update(['year_level' => $change->to_level]);
            $outcome = $updated === 1 ? 'applied' : 'stale';
            $change->update(['outcome' => $outcome]);
            $counts[$outcome]++;
        });
        $run->update(['status' => 'applied', 'applied_at' => now()]);
        $this->audit->record('year_level.applied', $run, 'Apply the approved year-level changes', $counts);

        return true;
    }

    private function invalid(string $key): never
    {
        throw ValidationException::withMessages(['year_level' => __('progression.errors.'.$key)]);
    }
}
