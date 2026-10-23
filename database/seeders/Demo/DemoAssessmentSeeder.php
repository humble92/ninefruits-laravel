<?php

namespace Database\Seeders\Demo;

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Actions\RegisterForAssessment;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Person\Enums\StudentStatus;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Person\StudentRecord;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Examinations: two past English sessions with published results, a computer exam with
 * pass/fail results, an upcoming English session open for registration, TOPIK results
 * recorded from certificates, and the English exam as a graduation requirement.
 */
class DemoAssessmentSeeder extends Seeder
{
    public function run(DemoOptions $options, RegisterForAssessment $register, RecordAssessmentResult $results): void
    {
        $registrar = User::query()->where('email', 'registrar@'.config('setup.demo.email_domain'))->first();
        $english = AssessmentType::query()->where('code', 'ENG_COMP')->with('components')->first();
        $computer = AssessmentType::query()->where('code', 'COMP_EXAM')->first();
        $topik = AssessmentType::query()->where('code', 'TOPIK')->first();

        if ($registrar === null || $english === null || $computer === null || $topik === null || AssessmentSession::query()->exists()) {
            return;
        }

        $students = StudentRecord::query()->where('status', StudentStatus::Enrolled)->inRandomOrder()->limit($options->scale(40))->get();
        $today = CarbonImmutable::today();

        foreach ([$today->subMonths(7), $today->subMonths(1)] as $index => $heldDate) {
            $session = AssessmentSession::query()->create([
                'assessment_type_id' => $english->id, 'held_date' => $heldDate, 'venue' => 'Main Hall 101',
                'registration_opens_at' => $heldDate->subWeeks(4), 'registration_closes_at' => $heldDate->subWeek(), 'capacity' => 200,
            ]);

            foreach ($students->slice($index * 10, 20) as $student) {
                // Past sessions: registrations are written directly (nobody registers after the date).
                $registration = AssessmentRegistration::query()->create([
                    'assessment_session_id' => $session->id, 'student_record_id' => $student->id, 'registered_at' => $heldDate->subWeeks(2), 'registered_by_id' => $registrar->id,
                ]);

                if (mt_rand(1, 12) === 1) {
                    $register->markAbsent($registration, $registrar);

                    continue;
                }

                $scores = $english->components->mapWithKeys(fn ($component) => [$component->code => mt_rand(8, 25)])->all();
                $results->forRegistration($registration, $scores, null, $registrar);
            }

            $results->publish($session, $registrar);
        }

        $computerSession = AssessmentSession::query()->create(['assessment_type_id' => $computer->id, 'held_date' => $today->subMonths(2), 'venue' => 'Computer Lab B']);

        foreach ($students->slice(5, 15) as $student) {
            $registration = AssessmentRegistration::query()->create([
                'assessment_session_id' => $computerSession->id, 'student_record_id' => $student->id, 'registered_at' => $today->subMonths(3), 'registered_by_id' => $registrar->id,
            ]);
            $results->forRegistration($registration, [], mt_rand(1, 5) !== 1, $registrar);
        }

        $results->publish($computerSession, $registrar);

        $upcoming = AssessmentSession::query()->create([
            'assessment_type_id' => $english->id, 'held_date' => $today->addWeeks(5), 'venue' => 'Main Hall 101',
            'registration_opens_at' => $today->subDays(3), 'registration_closes_at' => $today->addWeeks(3), 'capacity' => 150,
        ]);

        foreach ($students->slice(25, 6) as $student) {
            $register->register($upcoming, $student, $registrar, byStaff: true);
        }

        foreach ($students->slice(0, 4) as $index => $student) {
            $level = 3 + $index % 4;
            $results->external($student, $topik, $today->subMonths(3 + $index), true, $registrar, 150 + $level * 30, "TOPIK {$level}", 'TOPIK-'.$today->year.'-'.str_pad((string) (1000 + $index), 6, '0', STR_PAD_LEFT));
        }

        // The English exam is a graduation requirement in every primary curriculum.
        foreach (CurriculumRequirementSet::query()->where('program_role', 'primary')->get() as $set) {
            CurriculumRequirement::query()->create([
                'curriculum_requirement_set_id' => $set->id, 'kind' => RequirementKind::AssessmentPass, 'assessment_type_id' => $english->id,
                'name' => ['en' => 'English comprehensive examination', 'ko' => '영어종합시험 합격', 'zh_CN' => '英语综合考试合格'], 'sort_order' => 90,
            ]);
        }
    }
}
