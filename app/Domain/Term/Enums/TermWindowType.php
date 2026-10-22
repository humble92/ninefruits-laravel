<?php

namespace App\Domain\Term\Enums;

/**
 * Business periods within a term (legacy SSOBJDATE columns). Windows about courses belong
 * to a term session, because a course's deadlines follow its session (a second-half course
 * has its own add/drop and grading); the others concern students or the whole term.
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
    case AdditionalProgramApplication = 'additional_program_application';

    /**
     * Windows that belong to a session (deadlines of the courses in it).
     *
     * @return list<self>
     */
    public static function courseScoped(): array
    {
        return [self::AddDrop, self::Withdrawal, self::GradeEntry, self::GradeAppeal, self::CourseEvaluation];
    }

    public function isCourseScoped(): bool
    {
        return in_array($this, self::courseScoped(), true);
    }

    public function label(): string
    {
        return __('reference.term_window_types.'.$this->value);
    }
}
