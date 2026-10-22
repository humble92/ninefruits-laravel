<?php

namespace Database\Seeders\Profiles;

use App\Models\Curriculum\CourseVersion;
use App\Models\Organization\OrgUnit;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Term\TermType;
use App\Support\Setup\SetupProfile;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Database\Seeder;

/**
 * The legacy YUST registration rules as settings (docs/porting-plan.md section 10.6):
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
 * Idempotent: policies with the same name are updated, not duplicated.
 */
class YustLegacyRegistrationProfileSeeder extends Seeder implements SetupProfile
{
    public const array NURSING_UNIT_CODES = ['NURS'];

    public static function key(): string
    {
        return 'yust-legacy-registration';
    }

    public static function label(): string
    {
        return 'YUST legacy registration rules';
    }

    public static function description(): string
    {
        return 'Credit limits by admission year and nursing, credit bank courses outside the limit (needs korean-university)';
    }

    public function run(): void
    {
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
    }
}
