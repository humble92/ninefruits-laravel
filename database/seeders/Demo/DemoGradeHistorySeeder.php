<?php

namespace Database\Seeders\Demo;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Actions\CloseTerm;
use App\Domain\Offering\Actions\CopySections;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Illuminate\Database\Seeder;

/**
 * Last term's grades: the sections copied from the current term, finalized grades for the
 * students (a few struggling ones get an academic warning, a few top ones honors), then
 * term records, standing and honors as term close makes them. Runs before the
 * registration demo, so registering a failed course again becomes a retake.
 */
class DemoGradeHistorySeeder extends Seeder
{
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
        $seats = $sections->mapWithKeys(fn (Section $section) => [$section->id => 0])->all();

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

                if ($seats[$section->id] >= $section->capacity || isset($taken[$section->course_id]) || ! $section->admits($record)) {
                    continue;
                }

                Enrollment::factory()->inSection($section)->completed($this->grade($weights, $section))
                    ->create(['student_record_id' => $record->id, 'enrolled_at' => $past->start_date->subDays(10), 'source' => 'staff']);
                $seats[$section->id]++;
                $taken[$section->course_id] = true;
            }
        }

        foreach ($seats as $sectionId => $count) {
            Section::query()->whereKey($sectionId)->toBase()->update(['enrolled_count' => $count]);
        }

        $close->closeRecords($past, $students->modelKeys());
        $close->finish($past, null, 'Demo data');
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
