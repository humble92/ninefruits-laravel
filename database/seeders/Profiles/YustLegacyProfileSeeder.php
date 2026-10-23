<?php

namespace Database\Seeders\Profiles;

use App\Domain\Grading\Settings\GradingSettings;
use App\Models\Curriculum\CourseVersion;
use App\Models\Organization\OrgUnit;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Term\TermType;
use App\Support\Setup\SetupProfile;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Database\Seeder;

/**
 * The legacy YUST rules as settings.
 *
 * Registration (porting/porting-plan.md section 10.6):
 *
 * | Students            | Spring, fall | Summer, winter |
 * |---------------------|--------------|----------------|
 * | 2014 on, nursing    | 21           | 6              |
 * | 2014 on, others     | 18           | 5              |
 * | 2013 and before     | 20 nursing, 17 others | 6     |
 *
 * The legacy code applied 18 to nursing students from 2014 (a typo, section 3.4); here
 * they get 21. Nursing is the org unit with code NURS (and units below it); without it the
 * nursing rows are not created and the others apply to everyone. Courses whose codes
 * start with 009 or 900 (credit bank) do not count toward the limit.
 *
 * Holds (legacy SRDENY), overrides (SRALLOW), section eligibility (TARGET_ST*),
 * prerequisites, time conflicts, duplicates and retakes are the default rule set.
 * Grading (legacy SSCOREDEF and the GPA views, porting/decisions/0011-grades-and-term-records.md):
 * the 4.3 scale with minus grades (A+ 4.3, confirmed 2026-10-22), P/N and I is the
 * institution's scale and absolute evaluation base; score ranges are in steps of three
 * points (the legacy entered letters only). GPA rounded to 3 decimals; the latest attempt
 * of a retake counts; a warning below a term GPA of 1.3 in regular terms; dismissal review
 * after 4 warnings in all. The legacy honors list was a plain top N, so every student with
 * a GPA is ranked.
 *
 * Idempotent: policies with the same name are updated, not duplicated.
 */
class YustLegacyProfileSeeder extends Seeder implements SetupProfile
{
    public const array NURSING_UNIT_CODES = ['NURS'];

    public static function key(): string
    {
        return 'yust-legacy';
    }

    public static function label(): string
    {
        return 'YUST legacy rules';
    }

    public static function description(): string
    {
        return 'Credit limits by admission year and nursing, credit bank courses outside the limit, the 4.3 grade scale and grading policy (includes korean-university)';
    }

    public function run(): void
    {
        // Before the Korean data, so the 4.3 scale is the one fixed as the institution's.
        $this->gradeScale();
        $this->call(KoreanUniversityProfileSeeder::class);

        $types = TermType::query()->pluck('id', 'code');
        $nursing = OrgUnit::query()->whereIn('code', self::NURSING_UNIT_CODES)->pluck('id')->all();
        $from2014 = ['admission_year_from' => 2014];
        $until2013 = ['admission_year_to' => 2013];

        $policies = [
            // [name, term types, eligibility, max, priority]
            ['Regular 2014+ nursing', ['spring', 'fall'], [...$from2014, 'org_unit_ids' => $nursing], 21, 40, true],
            ['Regular 2014+', ['spring', 'fall'], $from2014, 18, 30, false],
            ['Regular 2013- nursing', ['spring', 'fall'], [...$until2013, 'org_unit_ids' => $nursing], 20, 20, true],
            ['Regular 2013-', ['spring', 'fall'], $until2013, 17, 10, false],
            ['Seasonal 2014+ nursing', ['summer', 'winter'], [...$from2014, 'org_unit_ids' => $nursing], 6, 40, true],
            ['Seasonal 2014+', ['summer', 'winter'], $from2014, 5, 30, false],
            ['Seasonal 2013-', ['summer', 'winter'], $until2013, 6, 10, false],
        ];

        foreach ($policies as [$name, $termTypes, $eligibility, $max, $priority, $needsNursing]) {
            if ($needsNursing && $nursing === []) {
                continue;
            }

            foreach ($termTypes as $type) {
                if (! isset($types[$type])) {
                    continue;
                }

                $policy = CreditLimitPolicy::query()->where('term_type_id', $types[$type])->get()
                    ->first(fn (CreditLimitPolicy $policy) => $policy->getTranslation('name', 'en', false) === "{$name} ({$type})")
                    ?? new CreditLimitPolicy;

                $policy->fill([
                    'name' => ['en' => "{$name} ({$type})"],
                    'term_type_id' => $types[$type],
                    'eligibility' => TargetCriteria::fromArray($eligibility),
                    'max_credits' => $max,
                    'priority' => $priority,
                ])->save();
            }
        }

        CourseVersion::query()->whereHas('course', fn ($course) => $course->where('code', 'like', '009%')->orWhere('code', 'like', '900%'))
            ->update(['counts_toward_credit_limit' => false]);

        $this->gradingPolicy();
    }

    /**
     * The 4.3 scale is the institution's scale unless one was fixed already (e.g. by
     * setup:install --grade-scale).
     */
    private function gradeScale(): void
    {
        KoreanUniversityProfileSeeder::seedScale('YUST43', ['en' => '4.3 scale (YUST)', 'ko' => '4.3 만점(YUST)', 'zh_CN' => '4.3 分制（YUST）'], 4.3, true,
            KoreanUniversityProfileSeeder::scale43(['P' => [true], 'N' => [false], 'I' => [false, 'both']]));
    }

    private function gradingPolicy(): void
    {
        $settings = app(GradingSettings::class);
        $settings->gpa_decimals = 3;
        $settings->gpa_rounding = 'round';
        $settings->retake_policy = 'latest';
        $settings->retake_max_grade = null;
        $settings->standing_regular_terms_only = true;
        $settings->warning_gpa_below = 1.3;
        $settings->dismissal_warning_count = 4;
        $settings->dismissal_consecutive_only = false;
        $settings->honors_min_gpa = 0;
        $settings->honors_min_credits = 0;
        $settings->honors_exclude_failing = false;
        $settings->save();
    }
}
