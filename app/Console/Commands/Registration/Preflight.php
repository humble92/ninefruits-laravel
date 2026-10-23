<?php

namespace App\Console\Commands\Registration;

use App\Console\Commands\AuditedCommand;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Services\RuleCatalog;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\RegistrationHold;
use App\Models\Registration\RegistrationRuleSet;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Checks a term before registration opens (porting/porting-plan.md section 13.1).
 */
#[Signature('registration:preflight {term : Term code, e.g. 2027-SP}')]
#[Description('Check a term before registration opens: windows, rule set, sections, seats, credit limits, holds')]
class Preflight extends Command
{
    public function handle(RuleCatalog $catalog): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $problems = [];
        $info = [];
        $windows = TermWindow::query()->where('term_id', $term->id)->get();

        foreach ([TermWindowType::Registration, TermWindowType::AddDrop] as $type) {
            $count = $windows->where('window_type', $type)->count();
            $count === 0 ? $problems[] = "No {$type->value} window." : $info[] = "{$count} {$type->value} window(s).";
        }

        $ruleSet = RegistrationRuleSet::query()->where('term_id', $term->id)->with('rules')->first();
        $info[] = $ruleSet === null
            ? 'No rule set: catalog defaults apply ('.count($catalog->all()).' rules).'
            : 'Rule set with '.$ruleSet->rules->where('is_enabled', true)->count().' enabled rule(s).';

        $sections = Section::query()->where('term_id', $term->id)->with(['seatGroups', 'meetings', 'version'])->get();
        $published = $sections->where('status', SectionStatus::Published);
        $info[] = "{$published->count()} published section(s), {$sections->where('status', SectionStatus::Draft)->count()} draft(s).";

        foreach ($published as $section) {
            if ($section->capacity === 0) {
                $problems[] = "{$section->label()}: capacity 0.";
            }

            if ($section->seatGroups->sum('capacity') > $section->capacity) {
                $problems[] = "{$section->label()}: seat groups exceed the capacity.";
            }

            if (! $section->version->isEffectiveOn($section->session()->value('start_date'))) {
                $problems[] = "{$section->label()}: course version not in effect at the session start.";
            }
        }

        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->get();
        $unlimited = $students->filter(fn (StudentRecord $record) => CreditLimitPolicy::applicableTo($record, $term->term_type_id) === null)->count();

        if ($unlimited > 0) {
            $problems[] = "{$unlimited} enrolled student(s) match no credit limit policy (no limit).";
        }

        $held = RegistrationHold::query()->where('blocks_registration', true)->inForce()->distinct()->count('student_record_id');
        $info[] = "{$held} student(s) with a hold blocking registration.";

        foreach ($info as $line) {
            $this->components->info($line);
        }

        foreach ($problems as $line) {
            $this->components->warn($line);
        }

        return $problems === [] ? self::SUCCESS : AuditedCommand::PROBLEMS_FOUND;
    }
}
