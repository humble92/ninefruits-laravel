<?php

namespace Database\Seeders\Demo;

use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Actions\SaveGrades;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Services\GradeOptions;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Grading\GradeChangeRequest;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Database\Seeder;

/**
 * Grade entry in the current term for demonstration: every third section evaluated
 * absolutely (scores), a grade entry window open now, one section with every grade
 * submitted (ready to finalize), one with drafts, and a pending grade change request on
 * last term's grades by its instructor.
 */
class DemoGradingSeeder extends Seeder
{
    public function run(SaveGrades $grades, GradeOptions $options, ManageGradeChanges $changes): void
    {
        $registrar = User::query()->where('email', 'registrar@'.config('setup.demo.email_domain'))->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

        if ($registrar === null || $current === null || GradeChangeRequest::query()->exists()) {
            return;
        }

        TermWindow::query()->create([
            'term_id' => $current->id,
            'term_session_id' => $current->fullTermSession()->id,
            'window_type' => TermWindowType::GradeEntry,
            'name' => ['en' => 'Demo grade entry', 'ko' => '시연용 성적 입력', 'zh_CN' => '演示成绩录入'],
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(14),
            'eligibility' => TargetCriteria::everyone(),
        ]);

        // Every third section of the term is evaluated absolutely (scores), the rest relatively.
        Section::query()->where('term_id', $current->id)->whereRaw('id % 3 = 0')->update(['evaluation_method' => EvaluationMethod::Absolute]);

        $instructor = User::query()->where('email', 'instructor@'.config('setup.demo.email_domain'))->first();
        // An absolutely evaluated section submitted with scores, a relative one half done.
        $candidates = fn (EvaluationMethod $method) => Section::query()->where('term_id', $current->id)->where('enrolled_count', '>', 0)
            ->where('evaluation_method', $method)
            ->when($instructor?->person_id, fn ($query, $personId) => $query->whereDoesntHave('instructors', fn ($instructors) => $instructors->where('person_id', $personId)))
            ->orderBy('id')->first();
        $sections = collect([$candidates(EvaluationMethod::Absolute), $candidates(EvaluationMethod::Relative)])->filter();

        foreach ($sections->values() as $index => $section) {
            $enrollments = Enrollment::query()->where('section_id', $section->id)->where('status', EnrollmentStatus::Enrolled)->with(['version', 'section'])->get();
            $entered = [];

            foreach ($enrollments as $position => $enrollment) {
                if ($index === 1 && $position % 2 === 1) {
                    continue; // the second section is half done
                }

                if ($options->takesScore($enrollment)) {
                    $entered[$enrollment->id] = ['score' => (string) mt_rand(55, 99)];
                } else {
                    $codes = $options->for($enrollment)->pluck('code')->all();
                    $entered[$enrollment->id] = ['grade_code' => $codes[array_rand($codes)]];
                }
            }

            $grades->save($section, $entered, $registrar);

            if ($index === 0) {
                $grades->submit($section, $registrar);
            }
        }

        $this->changeRequest($changes);
    }

    /**
     * Step by step: a nested whereHas over sections, instructors, people and users is slow
     * under row level security.
     */
    private function changeRequest(ManageGradeChanges $changes): void
    {
        $requester = User::query()->whereNotNull('person_id')
            ->whereIn('person_id', Instructor::query()->select('person_id'))->orderBy('id')->first();
        $sectionIds = $requester === null ? [] : Section::query()->taughtBy($requester->person_id)->pluck('id')->all();
        $enrollment = Enrollment::query()->whereIn('section_id', $sectionIds)->where('grade_status', GradeStatus::Finalized)
            ->whereNotNull('grade_points')->with('version')->orderBy('id')->first();

        if ($requester !== null && $enrollment !== null) {
            $changes->request($enrollment, $enrollment->grade_code === 'A0' ? 'B+' : 'A0', 'The final project was graded before the late submission was added.', $requester);
        }
    }
}
