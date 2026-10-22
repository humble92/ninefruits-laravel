<?php

namespace Database\Seeders\Demo;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Actions\ManageHolds;
use App\Domain\Registration\Actions\PrepareRuleSet;
use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Registration\Enums\RuleCode;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\RegistrationRuleSet;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Registration demo: rule sets for the current and next term, credit limits, current-term
 * registrations entered by the registrar (the registration period is over), a full
 * next-term section with a waitlist, and two holds. Everything goes through the
 * registration actions, so seat counters, attempts and audit records are consistent.
 */
class DemoRegistrationSeeder extends Seeder
{
    private const string REASON = 'Demo data';

    public function run(DemoOptions $options, PrepareRuleSet $prepare, EnrollStudent $enroll, ManageHolds $holds): void
    {
        $registrar = User::query()->where('email', 'registrar@'.config('setup.demo.email_domain'))->first();
        $current = Term::query()->where('status', 'published')->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();
        $next = Term::query()->where('status', 'published')->where('start_date', '>', now()->toDateString())->orderBy('start_date')->first();

        if ($registrar === null || $current === null) {
            return;
        }

        $currentSet = RegistrationRuleSet::query()->where('term_id', $current->id)->first() ?? $prepare->handle($current, null, $registrar);

        if ($next !== null && ! RegistrationRuleSet::query()->where('term_id', $next->id)->exists()) {
            $prepare->handle($next, $currentSet, $registrar);
        }

        $this->creditLimits();

        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->with('person')->inRandomOrder()
            ->limit($options->scale(60))->get();
        $this->registrations($current, $students, $enroll, $registrar);

        if ($next !== null) {
            $this->waitlist($next, $enroll, $registrar);
        }

        foreach ($students->take(2) as $record) {
            $holds->place($record, HoldType::Financial, 'Unpaid tuition (demo)', $registrar);
        }
    }

    private function creditLimits(): void
    {
        if (CreditLimitPolicy::query()->exists()) {
            return;
        }

        foreach (TermType::query()->get() as $type) {
            CreditLimitPolicy::query()->create([
                'name' => $type->is_regular
                    ? ['en' => 'Regular term', 'ko' => '정규학기', 'zh_CN' => '正常学期']
                    : ['en' => 'Seasonal term', 'ko' => '계절학기', 'zh_CN' => '短学期'],
                'term_type_id' => $type->id,
                'eligibility' => TargetCriteria::everyone(),
                'max_credits' => $type->is_regular ? 18 : 6,
                'priority' => 0,
            ]);
        }
    }

    /**
     * Up to five sections per student, chosen from the sections that admit them.
     *
     * @param  Collection<int, StudentRecord>  $students
     */
    private function registrations(Term $term, Collection $students, EnrollStudent $enroll, User $registrar): void
    {
        $sections = Section::query()->where('term_id', $term->id)->where('status', SectionStatus::Published)
            ->with(['course', 'version', 'session'])->get();

        foreach ($students as $record) {
            $candidates = $sections->filter(fn (Section $section) => $section->admits($record))->shuffle()->take(10);
            $registered = 0;

            foreach ($candidates as $section) {
                $outcome = $enroll->handle($record, $section, $registrar, new EnrollOptions(waive: [RuleCode::Window], reason: self::REASON));
                $registered += $outcome->succeeded() ? 1 : 0;

                if ($registered >= 5) {
                    break;
                }
            }
        }
    }

    /**
     * One small next-term section: two seats taken, two students waiting.
     */
    private function waitlist(Term $term, EnrollStudent $enroll, User $registrar): void
    {
        $section = Section::query()->where('term_id', $term->id)->where('status', SectionStatus::Published)
            ->whereDoesntHave('seatGroups')->with(['course', 'version', 'session'])->orderBy('id')->first();

        if ($section === null) {
            return;
        }

        $section->forceFill(['capacity' => 2, 'waitlist_capacity' => 5])->save();
        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->get()
            ->filter(fn (StudentRecord $record) => $section->admits($record))->take(4);

        foreach ($students as $record) {
            $enroll->handle($record, $section, $registrar, new EnrollOptions(waitlistIfFull: true, waive: [RuleCode::Window], reason: self::REASON));
        }
    }
}
