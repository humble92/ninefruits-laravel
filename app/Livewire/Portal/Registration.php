<?php

namespace App\Livewire\Portal;

use App\Domain\Offering\Services\Timetable;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Domain\Registration\Actions\DropEnrollment;
use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Actions\LeaveWaitlist;
use App\Domain\Registration\Actions\ManageCart;
use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Data\RegistrationOutcome;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Registration\Services\SeatAllocator;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Offering\Section;
use App\Models\Offering\SectionMeeting;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Registration for students: open windows, cart, search, register, waitlist, drop and
 * withdraw, credits against the limit and the timetable. Every action goes through the
 * registration actions; the page only shows their results.
 */
#[Layout('components.layouts.portal')]
class Registration extends Component
{
    #[Url]
    public ?int $termId = null;

    public string $search = '';

    /** @var list<string> Messages of the last action */
    public array $messages = [];

    public string $messageTone = 'info';

    public function mount(CurrentStudentRecord $current, TermResolver $terms): void
    {
        $record = $current->get($this->user()) ?? abort(404);

        $this->termId ??= $terms->termsOpenFor(TermWindowType::Registration, $record)->first()->id
            ?? $terms->termsOpenFor(TermWindowType::RegistrationCart, $record)->first()->id
            ?? $this->terms($record)->first()?->id;
    }

    public function enroll(int $sectionId, bool $waitlist = false): void
    {
        $this->limit();
        $outcome = app(EnrollStudent::class)->handle($this->record(), $this->section($sectionId), $this->user(), new EnrollOptions(waitlistIfFull: $waitlist));
        $this->show($outcome);
    }

    public function addToCart(int $sectionId): void
    {
        $this->limit();

        try {
            $evaluation = app(ManageCart::class)->add($this->record(), $this->section($sectionId), $this->user());
            $this->say([__('registration.portal.added_to_cart'), ...$evaluation->blockingMessages(), ...$evaluation->warnings()], $evaluation->isBlocked() ? 'warning' : 'success');
        } catch (ValidationException $exception) {
            $this->say(collect($exception->errors())->flatten()->all(), 'error');
        }
    }

    public function removeFromCart(int $sectionId): void
    {
        app(ManageCart::class)->remove($this->record(), $sectionId, $this->user());
    }

    public function submitCart(): void
    {
        $this->limit();
        $lines = [];

        foreach (app(ManageCart::class)->submit($this->record(), Term::query()->findOrFail($this->termId), $this->user()) as $sectionId => $outcome) {
            $label = Section::query()->with('course')->find($sectionId)?->label();
            $lines[] = $label.': '.($outcome->succeeded() ? __('registration.portal.registered') : implode(' ', $outcome->messages()));
        }

        $this->say($lines === [] ? [__('registration.portal.cart_empty')] : $lines, 'info');
    }

    public function drop(int $enrollmentId, bool $withdraw = false): void
    {
        $enrollment = Enrollment::query()->where('student_record_id', $this->record()->id)->findOrFail($enrollmentId);

        try {
            $withdraw
                ? app(DropEnrollment::class)->withdraw($enrollment, $this->user())
                : app(DropEnrollment::class)->drop($enrollment, $this->user());
            $this->say([__($withdraw ? 'registration.portal.withdrawn' : 'registration.portal.dropped')], 'success');
        } catch (ValidationException $exception) {
            $this->say(collect($exception->errors())->flatten()->all(), 'error');
        }
    }

    public function leaveWaitlist(int $entryId): void
    {
        app(LeaveWaitlist::class)->handle(WaitlistEntry::query()->where('student_record_id', $this->record()->id)->findOrFail($entryId), $this->user());
    }

    public function render(TermResolver $terms, SeatAllocator $seats, Timetable $timetable): View
    {
        $record = $this->record();
        $term = $this->termId === null ? null : Term::query()->find($this->termId);
        $now = CarbonImmutable::now();
        $enrollments = $this->enrollments($record, $term);
        $active = $enrollments->where('status', EnrollmentStatus::Enrolled);
        $policy = $term === null ? null : CreditLimitPolicy::applicableTo($record, $term->term_type_id);
        $registrationTerms = $terms->termsOpenFor(TermWindowType::Registration, $record);

        return view('livewire.portal.registration', [
            'record' => $record,
            'terms' => $this->terms($record),
            'term' => $term,
            'registrationOpen' => $term !== null && $registrationTerms->contains('id', $term->id),
            'cartOpen' => $term !== null && $terms->termsOpenFor(TermWindowType::RegistrationCart, $record)->contains('id', $term->id),
            'nextWindow' => $terms->nextWindow(TermWindowType::Registration, $record),
            'enrollments' => $enrollments,
            'activeCount' => $active->count(),
            'credits' => $active->filter(fn (Enrollment $enrollment) => $enrollment->counts_toward_credit_limit)->sum(fn (Enrollment $enrollment) => (float) $enrollment->credits),
            'limit' => $policy?->max_credits,
            'waitlist' => $this->waitlist($record, $term),
            'cart' => $term === null ? collect() : CartItem::query()->where('student_record_id', $record->id)->where('term_id', $term->id)->with(['section.course', 'section.version'])->get(),
            'results' => $this->searchResults($term),
            'seats' => fn (Section $section) => $seats->availability($section, $now),
            'canDrop' => fn (Enrollment $enrollment) => $registrationTerms->contains('id', $enrollment->term_id)
                || $terms->isOpenInSession(TermWindowType::AddDrop, $enrollment->section->session, $record),
            'canWithdraw' => fn (Enrollment $enrollment) => $terms->isOpenInSession(TermWindowType::Withdrawal, $enrollment->section->session, $record),
            'timetable' => $timetable->build($active->flatMap(fn (Enrollment $enrollment) => $enrollment->section->meetings), fn (SectionMeeting $meeting) => [
                $active->firstWhere('section_id', $meeting->section_id)?->section->label() ?? '',
                Timetable::roomLabel($meeting),
            ]),
        ])->title(__('registration.portal.title'));
    }

    /**
     * @return Collection<int, Enrollment>
     */
    private function enrollments(StudentRecord $record, ?Term $term): Collection
    {
        if ($term === null) {
            return collect();
        }

        return Enrollment::query()->where('student_record_id', $record->id)->where('term_id', $term->id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Withdrawn])
            ->with(['section.course', 'section.version', 'section.session', 'section.meetings.room.building'])
            ->get()->sortBy(fn (Enrollment $enrollment) => $enrollment->section->label())->values();
    }

    /**
     * @return Collection<int, WaitlistEntry>
     */
    private function waitlist(StudentRecord $record, ?Term $term): Collection
    {
        if ($term === null) {
            return collect();
        }

        return WaitlistEntry::query()->where('student_record_id', $record->id)->where('status', WaitlistStatus::Waiting)
            ->whereHas('section', fn (Builder $section) => $section->where('term_id', $term->id))
            ->with('section.course')->get();
    }

    /**
     * @return Collection<int, Section>
     */
    private function searchResults(?Term $term): Collection
    {
        $search = trim($this->search);

        if ($term === null || mb_strlen($search) < 2) {
            return collect();
        }

        return Section::query()->published()->where('term_id', $term->id)
            ->where(fn (Builder $query) => $query
                ->whereHas('course', fn (Builder $course) => $course->where('code', 'ilike', $search.'%'))
                ->orWhereHas('version', fn (Builder $version) => $version->whereRaw('util.i18n_search_text(title) ILIKE ?', ['%'.$search.'%'])))
            ->with(['course', 'version', 'session', 'instructors.person', 'meetings.room.building'])
            ->withCount(['waitlistEntries as waiting_count' => fn (Builder $entry) => $entry->where('status', WaitlistStatus::Waiting)])
            ->limit(30)->get()->sortBy(fn (Section $section) => $section->label())->values();
    }

    /**
     * Terms that are running or coming, and terms the student has registrations in.
     *
     * @return EloquentCollection<int, Term>
     */
    private function terms(StudentRecord $record): EloquentCollection
    {
        return Term::query()->where('status', 'published')
            ->where(fn (Builder $query) => $query->where('end_date', '>=', now()->toDateString())->orWhereIn('id', $record->enrollments()->select('term_id')))
            ->orderBy('start_date')->get();
    }

    private function show(RegistrationOutcome $outcome): void
    {
        match ($outcome->outcome) {
            AttemptOutcome::Succeeded => $this->say([__('registration.portal.registered'), ...$outcome->messages()], 'success'),
            AttemptOutcome::Waitlisted => $this->say(array_map(fn ($result) => (string) $result->message, array_slice($outcome->evaluation->results, -1)), 'info'),
            AttemptOutcome::Refused => $this->say($outcome->messages(), 'error'),
        };
    }

    /**
     * @param  array<int, string|null>  $messages
     */
    private function say(array $messages, string $tone): void
    {
        $this->messages = array_values(array_filter($messages, fn (?string $message) => $message !== null && $message !== ''));
        $this->messageTone = $tone;
    }

    /**
     * Per-user request limit (section 10.7): 30 registration actions a minute.
     */
    private function limit(): void
    {
        if (! RateLimiter::attempt('registration:'.$this->user()->id, 30, fn () => true)) {
            throw ValidationException::withMessages(['registration' => __('registration.errors.too_many_requests')]);
        }
    }

    private function section(int $sectionId): Section
    {
        return Section::query()->where('term_id', $this->termId)->findOrFail($sectionId);
    }

    private function record(): StudentRecord
    {
        return app(CurrentStudentRecord::class)->get($this->user()) ?? abort(404);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
