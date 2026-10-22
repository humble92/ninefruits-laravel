<?php

namespace App\Console\Commands\Registration;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Domain\Registration\Services\SeatAllocator;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Explains, rule by rule, whether a student could register for a section now (or at
 * --at), without registering. For "why can I not register?" questions.
 */
#[Signature('registration:simulate
    {student-number : Student number}
    {section : Section as COURSE-SECTION, e.g. CSE2010-01}
    {--term= : Term code (default: the section\'s latest term)}
    {--at= : Time to judge, e.g. "2027-02-10 10:00 Asia/Seoul"}')]
#[Description('Explain the registration rules for a student and a section without registering')]
class Simulate extends Command
{
    public function handle(RegistrationRuleEngine $engine, SeatAllocator $seats): int
    {
        $record = StudentRecord::query()->where('student_number', (string) $this->argument('student-number'))->first();
        [$courseCode, $sectionCode] = array_pad(explode('-', (string) $this->argument('section'), 2), 2, '');
        $section = Section::query()->whereHas('course', fn ($course) => $course->where('code', $courseCode))->where('section_code', $sectionCode)
            ->when($this->option('term'), fn ($query) => $query->where('term_id', Term::query()->where('code', (string) $this->option('term'))->value('id')))
            ->join('terms', 'terms.id', '=', 'sections.term_id')->orderByDesc('terms.start_date')->select('sections.*')->first();

        if ($record === null || $section === null) {
            $this->components->error($record === null ? 'Unknown student number.' : 'Unknown section.');

            return self::FAILURE;
        }

        $at = $this->option('at') ? CarbonImmutable::parse((string) $this->option('at')) : CarbonImmutable::now();
        $evaluation = $engine->evaluate(new RegistrationContext($record, $section, AttemptAction::Enroll, $at));
        $seat = $seats->find($section, $record, $at);

        $this->table(['Rule', 'Result', 'Severity', 'Message'], [
            ...array_map(fn ($result) => [
                $result->code->value,
                $result->passed ? 'pass' : ($result->overridden ? 'overridden' : 'FAIL'),
                $result->severity->value,
                $result->message ?? '',
            ], $evaluation->results),
            ['capacity', $seat === null ? 'FAIL' : 'pass', 'block', $seat === null ? 'No seat for this student' : (is_string($seat) ? 'General seat' : 'Reserved seat: '.$seat->name)],
        ]);

        $blocked = $evaluation->isBlocked() || $seat === null;
        $blocked ? $this->components->warn('Would be refused.') : $this->components->info('Would be registered.');

        return $blocked ? 2 : self::SUCCESS;
    }
}
