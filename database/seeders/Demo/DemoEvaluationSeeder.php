<?php

namespace Database\Seeders\Demo;

use App\Domain\Evaluation\Enums\QuestionType;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Evaluation\EvaluationAnswer;
use App\Models\Evaluation\EvaluationCompletion;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetCriteria;
use Illuminate\Database\Seeder;

/**
 * Course evaluation: last term evaluated by about seven in ten students with the results
 * published, and the current term's evaluation open now for demonstration.
 */
class DemoEvaluationSeeder extends Seeder
{
    private const array COMMENTS = [
        'The examples in class helped a lot.',
        'Please upload the slides before class.',
        '과제 설명이 조금 더 자세했으면 좋겠습니다.',
        '수업이 체계적이어서 좋았습니다.',
        'More practice problems before the exam would help.',
    ];

    public function run(): void
    {
        $form = EvaluationForm::query()->where('code', 'STANDARD')->with('questions')->first();
        $past = Term::query()->where('status', TermStatus::GradesClosed)->orderByDesc('start_date')->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

        if ($form === null || $past === null || $current === null || TermEvaluation::query()->exists()) {
            return;
        }

        foreach ([$past, $current] as $term) {
            TermEvaluation::query()->create(['term_id' => $term->id, 'evaluation_form_id' => $form->id, 'minimum_responses' => 3]);
        }

        $this->responses($past, $form);
        TermEvaluation::query()->where('term_id', $past->id)->update(['results_published_at' => $past->end_date->addDays(14)]);

        TermWindow::query()->create([
            'term_id' => $current->id,
            'term_session_id' => $current->fullTermSession()->id,
            'window_type' => TermWindowType::CourseEvaluation,
            'name' => ['en' => 'Demo course evaluation', 'ko' => '시연용 강의평가', 'zh_CN' => '演示课程评价'],
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(14),
            'eligibility' => TargetCriteria::everyone(),
        ]);
    }

    private function responses(Term $term, EvaluationForm $form): void
    {
        $enrollments = Enrollment::query()->where('term_id', $term->id)->where('status', EnrollmentStatus::Completed)->get();

        foreach ($enrollments as $enrollment) {
            if (mt_rand(1, 10) > 7) {
                continue;
            }

            // Each section gets a tendency, so results differ between sections.
            $center = 3 + ($enrollment->section_id % 3) * 0.5;
            EvaluationCompletion::query()->create(['enrollment_id' => $enrollment->id, 'completed_at' => $term->end_date->subDays(mt_rand(1, 10))]);
            $response = EvaluationResponse::query()->create(['term_id' => $term->id, 'section_id' => $enrollment->section_id, 'evaluation_form_id' => $form->id]);

            foreach ($form->questions as $question) {
                /** @var EvaluationQuestion $question */
                if ($question->question_type === QuestionType::Text) {
                    if (mt_rand(1, 4) === 1) {
                        EvaluationAnswer::query()->create(['evaluation_response_id' => $response->id, 'evaluation_question_id' => $question->id, 'comment' => self::COMMENTS[array_rand(self::COMMENTS)]]);
                    }

                    continue;
                }

                $value = (int) max(1, min($form->scale_points, round($center + mt_rand(-15, 15) / 10)));
                EvaluationAnswer::query()->create([
                    'evaluation_response_id' => $response->id,
                    'evaluation_question_id' => $question->id,
                    'value' => $question->is_reversed ? $form->scale_points + 1 - $value : $value,
                ]);
            }
        }
    }
}
