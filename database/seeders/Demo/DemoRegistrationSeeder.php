<?php

namespace Database\Seeders\Demo;

use App\Domain\Grading\Enums\AcademicStanding;
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
use Database\Seeders\Demo\Support\BulkRegistrar;
use Illuminate\Database\Seeder;

/**
 * Registration demo: rule sets for the current and next term, credit limits (12 credits
 * in regular terms for students on academic warning, otherwise 18 and 6), current-term
 * registrations entered by the registrar (the registration period is over), a full
 * next-term section with a waitlist, and two holds. The current-term registrations are
 * decided by the same rules and written in bulk (BulkRegistrar); the rest goes through the
 * registration actions.
 */
class DemoRegistrationSeeder extends Seeder
{
    private const string REASON = 'Demo data';

    public function run(DemoOptions $options, PrepareRuleSet $prepare, EnrollStudent $enroll, ManageHolds $holds, BulkRegistrar $bulk): void
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

        // Students on academic warning are among them, so the lower credit limit refuses some registrations.
        $warned = StudentRecord::query()->where('status', StudentStatus::Enrolled)->where('academic_standing', AcademicStanding::Warning)
            ->with('person')->inRandomOrder()->limit($options->scale(5))->get();
        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->whereNotIn('id', $warned->modelKeys())->with('person')
            ->inRandomOrder()->limit(max(0, $options->scale(60) - $warned->count()))->get()->concat($warned);
        $bulk->register($current, $students, $registrar, self::REASON);

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

            if ($type->is_regular) {
                CreditLimitPolicy::query()->create([
                    'name' => ['en' => 'Academic warning', 'ko' => '학사경고', 'zh_CN' => '学业警告', 'lo' => 'ເຕືອນທາງວິຊາການ', 'de' => 'Studienwarnung', 'fil' => 'Academic warning', 'fr' => 'Avertissement académique', 'ja' => '学業警告', 'mn' => 'Академик сануулга', 'ms' => 'Amaran akademik', 'ne' => 'शैक्षिक चेतावनी', 'ru' => 'Академическое предупреждение', 'th' => 'รอพินิจ', 'tr' => 'Akademik uyarı', 'uz' => 'Akademik ogohlantirish', 'vi' => 'Cảnh báo học vụ'],
                    'term_type_id' => $type->id,
                    'eligibility' => new TargetCriteria(academicStandings: [AcademicStanding::Warning->value]),
                    'max_credits' => 12,
                    'priority' => 10,
                ]);
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
