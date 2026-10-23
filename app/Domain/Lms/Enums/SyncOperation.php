<?php

namespace App\Domain\Lms\Enums;

enum SyncOperation: string
{
    case CourseSync = 'course.sync';
    case EnrollmentAdd = 'enrollment.add';
    case EnrollmentRemove = 'enrollment.remove';
    case GradesPull = 'grades.pull';
    case Reconcile = 'reconcile';

    public function label(): string
    {
        return __('lms.operations.'.$this->value);
    }
}
