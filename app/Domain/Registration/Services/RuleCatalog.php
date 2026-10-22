<?php

namespace App\Domain\Registration\Services;

use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Rules\CreditLimitRule;
use App\Domain\Registration\Rules\DuplicateCourseRule;
use App\Domain\Registration\Rules\EligibilityRule;
use App\Domain\Registration\Rules\HoldRule;
use App\Domain\Registration\Rules\RegistrationRule;
use App\Domain\Registration\Rules\RepeatCourseRule;
use App\Domain\Registration\Rules\RequisitesRule;
use App\Domain\Registration\Rules\SectionOpenRule;
use App\Domain\Registration\Rules\StudentStatusRule;
use App\Domain\Registration\Rules\TimeConflictRule;
use App\Domain\Registration\Rules\WindowRule;

/**
 * The registration rules in evaluation order. Rule sets configure them; they are not
 * stored as a catalog table (like permissions, the code is the catalog).
 */
class RuleCatalog
{
    /** @var array<string, class-string<RegistrationRule>> */
    private const array RULES = [
        RuleCode::Window->value => WindowRule::class,
        RuleCode::SectionOpen->value => SectionOpenRule::class,
        RuleCode::Hold->value => HoldRule::class,
        RuleCode::StudentStatus->value => StudentStatusRule::class,
        RuleCode::Eligibility->value => EligibilityRule::class,
        RuleCode::DuplicateCourse->value => DuplicateCourseRule::class,
        RuleCode::TimeConflict->value => TimeConflictRule::class,
        RuleCode::CreditLimit->value => CreditLimitRule::class,
        RuleCode::Requisites->value => RequisitesRule::class,
        RuleCode::RepeatCourse->value => RepeatCourseRule::class,
    ];

    /**
     * @return list<RegistrationRule>
     */
    public function all(): array
    {
        return array_values(array_map(fn (string $class) => app($class), self::RULES));
    }

    public function get(RuleCode $code): RegistrationRule
    {
        return app(self::RULES[$code->value]);
    }
}
