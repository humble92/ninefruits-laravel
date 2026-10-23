<?php

namespace App\Domain\Reporting\Services;

use App\Models\Organization\OrgUnit;
use App\Models\Term\Term;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The standard reports: each is a reporting view, the parameters it takes and the columns
 * it shows. No business rule lives here; the views and the caller's row level security
 * decide what comes back.
 */
class ReportCatalog
{
    /** @var array<string, array{view: string, params: list<string>, columns: list<string>, order: string}> */
    public const array REPORTS = [
        'students' => ['view' => 'students', 'params' => ['org_unit', 'status', 'year_level'], 'columns' => ['student_number', 'student_name', 'program_name', 'org_unit_name', 'year_level', 'status', 'admission_date', 'graduation_date', 'degree_number'], 'order' => 'student_number'],
        'headcount' => ['view' => 'headcount', 'params' => ['org_unit', 'status'], 'columns' => ['org_unit_code', 'org_unit_name', 'program_code', 'program_name', 'year_level', 'status', 'students'], 'order' => 'org_unit_code, program_code, year_level, status'],
        'term_records' => ['view' => 'term_records', 'params' => ['term', 'standing'], 'columns' => ['student_number', 'term_code', 'attempted_credits', 'earned_credits', 'term_gpa', 'cumulative_earned_credits', 'cumulative_gpa', 'academic_standing', 'warning_count', 'is_honors', 'honors_rank'], 'order' => 'student_number'],
        'graduation_candidates' => ['view' => 'degree_audits', 'params' => ['satisfied'], 'columns' => ['student_number', 'program_code', 'program_name', 'program_role', 'is_satisfied', 'earned_credits', 'required_credits', 'cumulative_gpa', 'audited_at'], 'order' => 'student_number'],
        'status_changes' => ['view' => 'status_changes', 'params' => ['term', 'date_from', 'date_to'], 'columns' => ['student_number', 'type_name', 'from_status', 'to_status', 'effective_date', 'term_code', 'status', 'decided_at'], 'order' => 'effective_date, student_number'],
        'section_enrollments' => ['view' => 'section_enrollments', 'params' => ['term', 'org_unit'], 'columns' => ['term_code', 'course_code', 'course_title', 'section_code', 'org_unit_name', 'instructors', 'status', 'capacity', 'enrolled', 'waiting'], 'order' => 'course_code, section_code'],
        'not_registered' => ['view' => 'students', 'params' => ['term', 'org_unit'], 'columns' => ['student_number', 'student_name', 'program_name', 'org_unit_name', 'year_level', 'status'], 'order' => 'student_number'],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::REPORTS);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, \stdClass>
     */
    public function rows(string $key, array $params, int $limit = 5000): Collection
    {
        return $this->query($key, $params)->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function query(string $key, array $params): Builder
    {
        $report = self::REPORTS[$key] ?? throw new \InvalidArgumentException("Unknown report [{$key}].");
        $query = DB::table('reporting.'.$report['view'])->select($report['columns'])->orderByRaw($report['order']);
        $orgUnitIds = filled($params['org_unit'] ?? null) ? [(int) $params['org_unit'], ...OrgUnit::descendantIdsOf([(int) $params['org_unit']])] : null;

        if ($key === 'not_registered') {
            $termId = (int) ($params['term'] ?? 0);
            $query->where('status', 'enrolled')->whereNotExists(fn ($sub) => $sub->from('enrollments')->whereColumn('enrollments.student_record_id', 'students.student_record_id')
                ->where('enrollments.term_id', $termId)->whereIn('enrollments.status', ['enrolled', 'completed']));
        }

        foreach ($report['params'] as $param) {
            $value = $params[$param] ?? null;

            if (blank($value) || ($key === 'not_registered' && $param === 'term')) {
                continue;
            }

            match ($param) {
                'org_unit' => $query->whereIn('org_unit_id', $orgUnitIds ?? []),
                'status' => $query->where('status', $value),
                'year_level' => $query->where('year_level', (int) $value),
                'term' => $query->where('term_code', Term::query()->findOrFail($value)->code),
                'standing' => $query->where('academic_standing', $value),
                'satisfied' => $query->where('is_satisfied', $value === 'yes'),
                'date_from' => $query->where('effective_date', '>=', $value),
                'date_to' => $query->where('effective_date', '<=', $value),
            };
        }

        return $query;
    }

    /**
     * UTF-8 CSV with a byte order mark.
     *
     * @param  array<string, mixed>  $params
     */
    public function csv(string $key, array $params): string
    {
        $columns = self::REPORTS[$key]['columns'];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_map(fn (string $column) => __('graduation.report_columns.'.$column), $columns), escape: '');

        foreach ($this->query($key, $params)->limit(100000)->cursor() as $row) {
            fputcsv($handle, array_map(fn (string $column) => self::cell($row->{$column} ?? null), $columns), escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Y' : 'N',
            default => (string) $value,
        };

        // A cell starting with =, +, - or @ would run as a formula in a spreadsheet (CSV injection).
        return preg_match('/^[=+\-@\t\r]/', $text) ? "'".$text : $text;
    }
}
