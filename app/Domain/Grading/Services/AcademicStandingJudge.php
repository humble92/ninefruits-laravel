<?php

namespace App\Domain\Grading\Services;

use App\Domain\Grading\Enums\AcademicStanding;
use App\Domain\Grading\Settings\GradingSettings;
use App\Models\Grading\TermRecord;
use App\Models\Term\Term;

/**
 * Academic standing at term close (legacy report_warn*, report_removal*).
 *
 * - Warning: term GPA strictly below the threshold. A term without GPA credits (only P/F)
 *   is no warning.
 * - Dismissal review: the warnings so far (all, or only consecutive ones) reach the count.
 * - With "regular terms only", summer and winter terms get no standing and do not break a
 *   run of consecutive warnings.
 */
class AcademicStandingJudge
{
    public function __construct(private readonly GradingSettings $settings) {}

    /**
     * Sets academic_standing and warning_count on the record (not saved). Requires the
     * record's term with its type.
     */
    public function judge(TermRecord $record, Term $term): TermRecord
    {
        $earlier = TermRecord::query()->where('student_record_id', $record->student_record_id)
            ->whereHas('term', fn ($query) => $query->where('start_date', '<', $term->start_date->toDateString()))
            ->whereNotNull('academic_standing')
            ->join('terms', 'terms.id', '=', 'term_records.term_id')->orderBy('terms.start_date')
            ->get(['term_records.*']);
        $previousCount = $this->count($earlier->pluck('academic_standing')->all());

        if ($this->settings->standing_regular_terms_only && ! $term->type->is_regular) {
            return $record->forceFill(['academic_standing' => null, 'warning_count' => $previousCount]);
        }

        $warned = $record->term_gpa !== null && (float) $record->term_gpa < $this->settings->warning_gpa_below;
        $count = $this->count([...$earlier->pluck('academic_standing')->all(), $warned ? AcademicStanding::Warning : AcademicStanding::Good]);
        $standing = match (true) {
            ! $warned => AcademicStanding::Good,
            $this->settings->dismissal_warning_count > 0 && $count >= $this->settings->dismissal_warning_count => AcademicStanding::DismissalReview,
            default => AcademicStanding::Warning,
        };

        return $record->forceFill(['academic_standing' => $standing, 'warning_count' => $count]);
    }

    /**
     * @param  list<AcademicStanding|null>  $standings  oldest first
     */
    private function count(array $standings): int
    {
        $isWarning = fn (?AcademicStanding $standing) => in_array($standing, [AcademicStanding::Warning, AcademicStanding::DismissalReview], true);

        if (! $this->settings->dismissal_consecutive_only) {
            return count(array_filter($standings, $isWarning));
        }

        $run = 0;

        foreach ($standings as $standing) {
            $run = $isWarning($standing) ? $run + 1 : 0;
        }

        return $run;
    }
}
