<?php

namespace App\Domain\Term\Services;

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetSubject;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Answers "which term?" per business question instead of a global current-term flag
 * (docs/porting-plan.md section 10.2). Past term grading, current instruction and next
 * term registration can all be active at the same time.
 *
 * Dates are judged in the institution time zone; window times are absolute (timestamptz).
 */
class TermResolver
{
    public function __construct(private readonly InstitutionSettings $institution) {}

    /**
     * Terms whose instruction period includes the institution's local date.
     *
     * @return Collection<int, Term>
     */
    public function inSession(?CarbonImmutable $at = null): Collection
    {
        $localDate = $this->localDate($at);

        return Term::query()
            ->where('status', '!=', TermStatus::Archived)
            ->whereDate('start_date', '<=', $localDate)
            ->whereDate('end_date', '>=', $localDate)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Windows of the type open at the moment, for terms that are published (or grades
     * closed). Planned and archived terms never have open windows.
     *
     * @return Collection<int, TermWindow>
     */
    public function openWindows(TermWindowType $type, ?CarbonImmutable $at = null): Collection
    {
        return TermWindow::query()
            ->with('term')
            ->where('window_type', $type)
            ->openAt($at ?? CarbonImmutable::now())
            ->whereHas('term', fn (Builder $term) => $term->whereIn('status', [TermStatus::Published, TermStatus::GradesClosed]))
            ->orderBy('opens_at')
            ->get();
    }

    /**
     * Terms with an open window of the type. With a subject, only windows whose
     * eligibility matches it, e.g. "terms this student can register for now".
     *
     * @return Collection<int, Term>
     */
    public function termsOpenFor(TermWindowType $type, ?TargetSubject $subject = null, ?CarbonImmutable $at = null): Collection
    {
        return $this->openWindows($type, $at)
            ->filter(fn (TermWindow $window) => $subject === null || $window->eligibility->matches($subject))
            ->map(fn (TermWindow $window) => $window->term)
            ->unique('id')
            ->sortBy('start_date')
            ->values();
    }

    /**
     * The next window of the type that has not opened yet, for "registration opens on ..."
     * messages.
     */
    public function nextWindow(TermWindowType $type, ?TargetSubject $subject = null, ?CarbonImmutable $at = null): ?TermWindow
    {
        return TermWindow::query()
            ->with('term')
            ->where('window_type', $type)
            ->where('opens_at', '>', $at ?? CarbonImmutable::now())
            ->whereHas('term', fn (Builder $term) => $term->whereIn('status', [TermStatus::Published, TermStatus::GradesClosed]))
            ->orderBy('opens_at')
            ->get()
            ->first(fn (TermWindow $window) => $subject === null || $window->eligibility->matches($subject));
    }

    public function timezone(): string
    {
        return $this->institution->timezone;
    }

    public function localDate(?CarbonImmutable $at = null): string
    {
        return ($at ?? CarbonImmutable::now())->setTimezone($this->institution->timezone)->toDateString();
    }
}
