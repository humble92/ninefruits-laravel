<?php

namespace Database\Seeders\Demo;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Actions\CloseTerm;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Offering\Actions\CopySections;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\Support\StudentSubject;
use Illuminate\Database\Seeder;

/**
 * Last term's grades: the sections copied from the current term, finalized grades for the
 * students (a few struggling ones get an academic warning, a few top ones honors), then
 * term records, standing and honors as term close makes them. Runs before the
 * registration demo, so registering a failed course again becomes a retake. The
 * enrollments are written in bulk.
 */
class DemoGradeHistorySeeder extends Seeder
{
    private const int CHUNK = 1000;

    /** grade => weight */
    private const array GRADES = ['A+' => 10, 'A0' => 15, 'B+' => 20, 'B0' => 20, 'C+' => 12, 'C0' => 8, 'D+' => 5, 'D0' => 4, 'F' => 6];

    private const array STRUGGLING = ['C0' => 2, 'D+' => 3, 'D0' => 3, 'F' => 4];

    private const array TOP = ['A+' => 6, 'A0' => 3, 'B+' => 1];

    public function run(DemoOptions $options, CopySections $copy, CloseTerm $close): void
    {
        $past = Term::query()->with('type')->where('status', TermStatus::GradesClosed)->orderByDesc('start_date')->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();
        $planner = User::query()->where('email', 'academic-affairs@'.config('setup.demo.email_domain'))->first();

        if ($past === null || $current === null || $planner === null || Section::query()->where('term_id', $past->id)->exists()) {
            return;
        }

        $copy->handle($current, $past, $planner);
        $sections = Section::query()->where('term_id', $past->id)->with(['version', 'course'])->get();
        Section::query()->whereKey($sections->modelKeys())->update(['status' => SectionStatus::Published]);

        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->inRandomOrder()->limit($options->scale(80))->get();
        $subjects = StudentSubject::forRecords($students);
        $scale = GradeScale::query()->where('is_default', true)->first() ?? GradeScale::factory()->korean()->create();
        $items = GradeScaleItem::query()->where('grade_scale_id', $scale->id)->get()->keyBy('code');
        $seats = $sections->mapWithKeys(fn (Section $section) => [$section->id => 0])->all();
        $now = CarbonImmutable::now();
        $rows = [];
        $graded = [];

        foreach ($students->values() as $index => $record) {
            $weights = match ($index % 15) {
                0 => self::STRUGGLING,
                1, 8 => self::TOP,
                default => self::GRADES,
            };
            $taken = [];

            foreach ($sections->shuffle() as $section) {
                if (count($taken) >= 5) {
                    break;
                }

                if ($seats[$section->id] >= $section->capacity || isset($taken[$section->course_id]) || ! $section->admits($subjects[$record->id])) {
                    continue;
                }

                $grade = $this->grade($weights, $section);
                $rows[] = $this->completed($record, $section, $past, $scale, $grade, $items->get($grade), $now);
                $seats[$section->id]++;
                $taken[$section->course_id] = true;
                $graded[$record->id] = $record->id;
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            Enrollment::query()->insert($chunk);
        }

        foreach ($seats as $sectionId => $count) {
            Section::query()->whereKey($sectionId)->toBase()->update(['enrolled_count' => $count]);
        }

        foreach (array_chunk(array_values($graded), self::CHUNK) as $chunk) {
            $close->closeRecords($past, $chunk);
        }

        $close->finish($past, null, 'Demo data');
    }

    /**
     * The row EnrollmentFactory::completed() would create: the course snapshot of the
     * section and a finalized grade of the default scale.
     *
     * @return array<string, mixed>
     */
    private function completed(StudentRecord $record, Section $section, Term $past, GradeScale $scale, string $grade, ?GradeScaleItem $item, CarbonImmutable $now): array
    {
        $enrollment = new Enrollment([
            'student_record_id' => $record->id,
            'section_id' => $section->id,
            'term_id' => $past->id,
            'course_id' => $section->course_id,
            'course_version_id' => $section->course_version_id,
            'credits' => $section->version->credits,
            'course_category_id' => $section->version->course_category_id,
            'counts_toward_credit_limit' => $section->version->counts_toward_credit_limit,
            'source' => EnrollmentSource::Staff,
            'enrolled_at' => $past->start_date->subDays(10),
        ]);
        $enrollment->forceFill([
            'status' => EnrollmentStatus::Completed,
            'grade_status' => GradeStatus::Finalized,
            'grade_scale_id' => $scale->id,
            'grade_code' => $grade,
            'grade_points' => $item?->grade_points,
            'counts_in_gpa' => $item->counts_in_gpa ?? false,
            'is_passed' => $item->is_passing ?? true,
            'grade_finalized_at' => $now,
        ]);

        return [...$enrollment->getAttributes(), 'created_at' => $now, 'updated_at' => $now];
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function grade(array $weights, Section $section): string
    {
        if ($section->version->grading_basis === GradingBasis::PassFail) {
            return mt_rand(1, 10) === 1 ? 'NP' : 'P';
        }

        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $grade => $weight) {
            if (($roll -= $weight) <= 0) {
                return $grade;
            }
        }

        return 'B0';
    }
}
