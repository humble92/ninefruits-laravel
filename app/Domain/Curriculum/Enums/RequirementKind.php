<?php

namespace App\Domain\Curriculum\Enums;

enum RequirementKind: string
{
    /** Minimum credits in a course category (and its subcategories). */
    case CategoryCredits = 'category_credits';
    /** Every listed course. */
    case RequiredCourses = 'required_courses';
    /** Minimum credits or courses from the listed courses. */
    case CourseGroup = 'course_group';

    public function label(): string
    {
        return __('curriculum.requirement_kinds.'.$this->value);
    }
}
