<?php

namespace App\Domain\Assessment\Services;

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Actions\RegisterForAssessment;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentSession;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

/**
 * Results of a session from a CSV: one row per student with `student_number`, a column
 * per area code (or `total` for kinds without areas), optional `passed` (Y/N, pass/fail
 * kinds) and `absent` (Y). Students not registered are registered by the importer.
 */
class AssessmentResultImporter
{
    public function __construct(private readonly RecordAssessmentResult $results, private readonly RegisterForAssessment $registrations) {}

    /**
     * @return array{rows: int, recorded: int, absent: int, errors: list<string>}
     */
    public function import(AssessmentSession $session, string $path, User $staff, bool $dryRun = false): array
    {
        $session->loadMissing('type.components');
        $csv = Reader::createFromPath($path);
        $csv->setHeaderOffset(0);
        $header = array_map('strtolower', $csv->getHeader());
        // Column names are matched case-insensitively; scores are keyed back by the component code.
        $codes = $session->type->components->mapWithKeys(fn ($component) => [strtolower($component->code) => $component->code])->all();
        $summary = ['rows' => 0, 'recorded' => 0, 'absent' => 0, 'errors' => []];

        if (! in_array('student_number', $header, true)) {
            $summary['errors'][] = 'Missing column: student_number';

            return $summary;
        }

        foreach ($csv->getRecords() as $index => $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $summary['rows']++;
            $line = $index + 1;
            $number = trim((string) ($row['student_number'] ?? ''));
            $record = $number === '' ? null : StudentRecord::query()->where('student_number', $number)->first();

            if ($record === null) {
                $summary['errors'][] = "Line {$line}: unknown student number [{$number}].";

                continue;
            }

            $registration = AssessmentRegistration::query()->where('assessment_session_id', $session->id)->where('student_record_id', $record->id)->first();

            try {
                if ($dryRun) {
                    if (! $this->isYes($row['absent'] ?? null)) {
                        $this->validate($row, $codes);
                    }

                    $summary[$this->isYes($row['absent'] ?? null) ? 'absent' : 'recorded']++;

                    continue;
                }

                $registration ??= $this->registrations->register($session, $record, $staff, byStaff: true);

                if ($this->isYes($row['absent'] ?? null)) {
                    $this->registrations->markAbsent($registration, $staff);
                    $summary['absent']++;

                    continue;
                }

                $scores = [];

                foreach ($codes as $column => $code) {
                    $scores[$code] = $row[$column] ?? null;
                }

                $passed = isset($row['passed']) && trim((string) $row['passed']) !== '' ? $this->isYes($row['passed']) : null;
                $total = isset($row['total']) && is_numeric($row['total']) ? (float) $row['total'] : null;
                $this->results->forRegistration($registration, $scores, $passed, $staff, $total, isset($row['note']) ? trim((string) $row['note']) : null);
                $summary['recorded']++;
            } catch (ValidationException $e) {
                $summary['errors'][] = "Line {$line}: ".implode(' ', array_merge(...array_values($e->errors())));
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, string>  $codes  lowercase column => component code
     */
    private function validate(array $row, array $codes): void
    {
        foreach ($codes as $column => $code) {
            if (! isset($row[$column]) || ! is_numeric($row[$column])) {
                throw ValidationException::withMessages(['scores' => "Missing or non-numeric score for [{$code}]."]);
            }
        }
    }

    private function isYes(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['y', 'yes', '1', 'true', 'p', 'pass'], true);
    }
}
