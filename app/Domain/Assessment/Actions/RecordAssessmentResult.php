<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Domain\Assessment\Enums\ResultSource;
use App\Domain\Assessment\Enums\ScoringMethod;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Assessment\AssessmentComponent;
use App\Models\Assessment\AssessmentComponentScore;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Results. For a session: the area scores, the total and whether it passed (the total
 * against the pass score and every area against its minimum; decided by hand for
 * pass/fail kinds). Legacy summed the areas against one cut line only, and silently
 * overwrote scores (cet_add2go). Entering again replaces the result and is audited.
 * External results (an outside body's test) carry the evidence file.
 */
class RecordAssessmentResult
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $scores  component code => score
     */
    public function forRegistration(AssessmentRegistration $registration, array $scores, ?bool $passed, User $staff, ?float $total = null, ?string $note = null, bool $applyingCorrection = false): AssessmentResult
    {
        $this->authorize($staff);
        // Loaded fresh: the session's publication state decides whether entry is still allowed.
        $registration->load(['session.type.components', 'studentRecord']);

        if ($registration->status === AssessmentRegistrationStatus::Cancelled) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.not_registered')]);
        }

        $session = $registration->session;

        // Published results are locked (decided 2026-10-23): changes go through a correction request.
        if ($session->results_published_at !== null && ! $applyingCorrection) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.published_locked')]);
        }

        $type = $session->type;
        [$componentScores, $total] = $this->componentScores($type, $scores, $total);
        $isPassed = $this->decide($type, $session->passScore(), $componentScores, $total, $passed);

        $result = DB::transaction(function () use ($registration, $session, $type, $componentScores, $total, $isPassed, $staff, $note) {
            $result = AssessmentResult::query()->updateOrCreate(
                ['assessment_registration_id' => $registration->id],
                [
                    'assessment_session_id' => $session->id, 'student_record_id' => $registration->student_record_id, 'assessment_type_id' => $type->id, 'source' => ResultSource::Session, 'taken_date' => $session->held_date, 'total_score' => $total, 'level' => null,
                    'is_passed' => $isPassed, 'note' => $note, 'entered_by_id' => $staff->id, 'entered_at' => CarbonImmutable::now(),
                ],
            );
            $result->componentScores()->delete();

            foreach ($componentScores as $componentId => $score) {
                AssessmentComponentScore::query()->create(['assessment_result_id' => $result->id, 'assessment_component_id' => $componentId, 'score' => $score]);
            }

            if ($registration->status === AssessmentRegistrationStatus::Absent) {
                $registration->forceFill(['status' => AssessmentRegistrationStatus::Registered])->save();
            }

            return $result;
        });

        $this->audit->record($applyingCorrection ? 'assessment.result_corrected' : 'assessment.result_recorded', $result, $note, [
            'session_id' => $session->id, 'student_number' => $registration->studentRecord->student_number, 'total' => $total, 'passed' => $isPassed, 'scores' => $scores,
        ], $staff);

        return $result;
    }

    public function external(StudentRecord $record, AssessmentType $type, CarbonImmutable $takenDate, bool $passed, User $staff, ?float $total = null, ?string $level = null, ?string $certificateNumber = null, ?string $note = null, ?UploadedFile $evidence = null): AssessmentResult
    {
        $this->authorize($staff);

        if (! $record->isVisibleTo($staff)) {
            throw new AuthorizationException;
        }

        if (! $type->allows_external) {
            throw ValidationException::withMessages(['assessment_type_id' => __('assessment.errors.no_external')]);
        }

        if ($takenDate->gt(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['taken_date' => __('assessment.errors.future_date')]);
        }

        $result = DB::transaction(function () use ($record, $type, $takenDate, $passed, $staff, $total, $level, $certificateNumber, $note, $evidence) {
            $result = AssessmentResult::query()->create([
                'student_record_id' => $record->id, 'assessment_type_id' => $type->id, 'source' => ResultSource::External, 'taken_date' => $takenDate, 'total_score' => $total,
                'level' => $level, 'is_passed' => $passed, 'certificate_number' => $certificateNumber, 'note' => $note, 'entered_by_id' => $staff->id, 'entered_at' => CarbonImmutable::now(),
            ]);

            if ($evidence !== null) {
                $result->addMedia($evidence)->toMediaCollection(AssessmentResult::EVIDENCE_COLLECTION);
            }

            return $result;
        });

        $this->audit->record('assessment.external_recorded', $result, $note, [
            'type' => $type->code, 'student_number' => $record->student_number, 'taken_date' => $takenDate->toDateString(), 'level' => $level, 'passed' => $passed, 'evidence' => $evidence !== null,
        ], $staff);

        return $result;
    }

    public function remove(AssessmentResult $result, User $staff, string $reason): void
    {
        $this->authorize($staff);

        if ($result->isLocked()) {
            throw ValidationException::withMessages(['result' => __('assessment.errors.published_locked')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $result->loadMissing('studentRecord');
        $this->audit->record('assessment.result_removed', $result, $reason, [
            'student_number' => $result->studentRecord->student_number, 'type_id' => $result->assessment_type_id, 'session_id' => $result->assessment_session_id, 'total' => $result->total_score, 'passed' => $result->is_passed,
        ], $staff);
        DB::transaction(fn () => $result->delete());
    }

    public function publish(AssessmentSession $session, User $staff): AssessmentSession
    {
        if (! $staff->can(Permission::AssessmentsRecord->value)) {
            throw new AuthorizationException;
        }

        if ($session->results_published_at !== null) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.already_published')]);
        }

        $session->forceFill(['results_published_at' => CarbonImmutable::now()])->save();
        $this->audit->record('assessment.results_published', $session, null, ['results' => $session->results()->count()], $staff);

        return $session;
    }

    /**
     * Component scores keyed by component id, and the total.
     *
     * @param  array<string, mixed>  $scores
     * @return array{array<int, float>, float|null}
     */
    private function componentScores(AssessmentType $type, array $scores, ?float $total): array
    {
        $byId = [];

        foreach ($type->components as $component) {
            /** @var AssessmentComponent $component */
            $value = $scores[$component->code] ?? null;

            if ($value === null || $value === '') {
                throw ValidationException::withMessages(['scores' => __('assessment.errors.score_missing', ['component' => $component->name])]);
            }

            if (! is_numeric($value) || (float) $value < 0 || (float) $value > (float) $component->max_score) {
                throw ValidationException::withMessages(['scores' => __('assessment.errors.score_range', ['component' => $component->name, 'max' => $component->max_score])]);
            }

            $byId[$component->id] = (float) $value;
        }

        if ($byId !== []) {
            $total = array_sum($byId);
        } elseif ($total !== null && ($total < 0 || ($type->max_score !== null && $total > (float) $type->max_score))) {
            throw ValidationException::withMessages(['total' => __('assessment.errors.total_range', ['max' => $type->max_score])]);
        }

        return [$byId, $total];
    }

    /**
     * @param  array<int, float>  $componentScores
     */
    private function decide(AssessmentType $type, ?string $passScore, array $componentScores, ?float $total, ?bool $passed): bool
    {
        if ($type->scoring === ScoringMethod::PassFail) {
            return $passed ?? throw ValidationException::withMessages(['passed' => __('assessment.errors.decision_required')]);
        }

        if ($passScore === null || $total === null) {
            return $passed ?? throw ValidationException::withMessages(['passed' => __('assessment.errors.no_pass_score')]);
        }

        foreach ($type->components as $component) {
            if ($component->min_score !== null && ($componentScores[$component->id] ?? 0) < (float) $component->min_score) {
                return false;
            }
        }

        return $total >= (float) $passScore;
    }

    private function authorize(User $staff): void
    {
        if (! $staff->can(Permission::AssessmentsRecord->value)) {
            throw new AuthorizationException;
        }
    }
}
