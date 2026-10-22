<?php

namespace App\Domain\Term\Enums;

/**
 * Business periods within a term (legacy SSOBJDATE columns).
 */
enum TermWindowType: string
{
    case RegistrationCart = 'registration_cart';
    case Registration = 'registration';
    case AddDrop = 'add_drop';
    case Withdrawal = 'withdrawal';
    case GradeEntry = 'grade_entry';
    case GradeAppeal = 'grade_appeal';
    case CourseEvaluation = 'course_evaluation';
    case GraduationApplication = 'graduation_application';

    public function label(): string
    {
        return __('reference.term_window_types.'.$this->value);
    }
}
