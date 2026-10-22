<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RegistrationOutcome;
use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * The cart (section 10.7): students collect sections while the cart window (or
 * registration) is open and see every rule's result in advance; only the window and the
 * section being published stop an item. When registration opens they submit the cart at
 * once instead of searching under load.
 */
class ManageCart
{
    public function __construct(
        private readonly RegistrationRuleEngine $engine,
        private readonly EnrollStudent $enroll,
    ) {}

    /**
     * @return RuleEvaluation The pre-check of the section for the student
     */
    public function add(StudentRecord $record, Section $section, User $student): RuleEvaluation
    {
        $this->authorize($record, $student);

        $evaluation = $this->engine->evaluate(new RegistrationContext($record, $section, AttemptAction::Cart, CarbonImmutable::now(), $student));
        $stopping = array_filter($evaluation->results, fn ($result) => $result->blocks() && in_array($result->code, [RuleCode::Window, RuleCode::SectionOpen, RuleCode::DuplicateCourse], true));

        if ($stopping !== []) {
            throw ValidationException::withMessages(['section' => array_values(array_map(fn ($result) => $result->message, $stopping))]);
        }

        CartItem::query()->firstOrCreate(['student_record_id' => $record->id, 'section_id' => $section->id], ['term_id' => $section->term_id]);

        return $evaluation;
    }

    public function remove(StudentRecord $record, int $sectionId, User $student): void
    {
        $this->authorize($record, $student);
        CartItem::query()->where('student_record_id', $record->id)->where('section_id', $sectionId)->delete();
    }

    /**
     * Registers every section in the cart for the term, in the order they were added.
     *
     * @return array<int, RegistrationOutcome> By section id
     */
    public function submit(StudentRecord $record, Term $term, User $student, bool $waitlistIfFull = false): array
    {
        $this->authorize($record, $student);
        $outcomes = [];

        foreach (CartItem::query()->where('student_record_id', $record->id)->where('term_id', $term->id)->orderBy('id')->with('section')->get() as $item) {
            $outcomes[$item->section_id] = $this->enroll->handle($record, $item->section, $student, new EnrollOptions(waitlistIfFull: $waitlistIfFull));
        }

        return $outcomes;
    }

    private function authorize(StudentRecord $record, User $student): void
    {
        if ($student->person_id === null || $student->person_id !== $record->person_id) {
            throw new AuthorizationException;
        }
    }
}
