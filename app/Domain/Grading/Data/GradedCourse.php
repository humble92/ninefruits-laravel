<?php

namespace App\Domain\Grading\Data;

use App\Models\Registration\Enrollment;

/**
 * A completed (finalized) enrollment as the GPA calculation sees it. Credits are kept in
 * tenths and grade points in hundredths so sums are exact.
 */
final readonly class GradedCourse
{
    public function __construct(
        public int $enrollmentId,
        public int $termId,
        /** Orders attempts: term start date, then enrollment id. */
        public string $termStart,
        public int $creditTenths,
        public ?int $pointHundredths,
        public bool $countsInGpa,
        public bool $isPassed,
        public ?int $repeatsEnrollmentId = null,
    ) {}

    /**
     * Requires the enrollment's term to be loaded.
     */
    public static function fromEnrollment(Enrollment $enrollment): self
    {
        return new self(
            enrollmentId: $enrollment->id,
            termId: $enrollment->term_id,
            termStart: $enrollment->term->start_date->toDateString(),
            creditTenths: (int) round((float) $enrollment->credits * 10),
            pointHundredths: $enrollment->grade_points === null ? null : (int) round((float) $enrollment->grade_points * 100),
            countsInGpa: (bool) $enrollment->counts_in_gpa,
            isPassed: (bool) $enrollment->is_passed,
            repeatsEnrollmentId: $enrollment->repeats_enrollment_id,
        );
    }
}
