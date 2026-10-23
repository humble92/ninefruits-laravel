<?php

namespace Database\Seeders\Profiles;

use App\Models\Admission\Application;
use App\Models\Assessment\AssessmentComponent;
use App\Models\Assessment\AssessmentType;
use App\Models\Certificate\CertificateTemplate;
use App\Models\Certificate\CertificateType;
use App\Models\Curriculum\CourseCategory;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Facility\Room;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Institution\LookupType;
use App\Models\Institution\LookupValue;
use App\Models\Term\TermType;
use App\Models\Term\TimeSlot;
use App\Support\Setup\SetupProfile;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Database\Seeders\Essential\LookupTypeSeeder;
use Illuminate\Database\Seeder;

/**
 * Typical Korean four-year university: spring/summer/fall/winter terms, general education
 * and major categories, a 4.5 grade scale, 50-minute periods from 09:00, common room
 * types, admission types and application documents. Idempotent; values administrators changed are kept.
 */
class KoreanUniversityProfileSeeder extends Seeder implements SetupProfile
{
    use SeedsTranslatedRows;

    public static function key(): string
    {
        return 'korean-university';
    }

    public static function label(): string
    {
        return 'Korean university';
    }

    public static function description(): string
    {
        return 'Spring/summer/fall/winter terms, 4.5 grade scale, course categories, periods from 09:00, room types, admission types and application documents';
    }

    public function run(): void
    {
        $this->call(LookupTypeSeeder::class);

        $this->termTypes();
        $this->courseCategories();
        $this->gradeScale();
        $this->evaluationForm();
        $this->certificateTypes();
        $this->assessmentTypes();
        $this->timeSlots();
        $this->roomTypes();
        $this->lookupValues(Application::ADMISSION_TYPE_LOOKUP, [
            'freshman' => ['en' => 'Freshman admission', 'ko' => '신입학', 'zh_CN' => '新生入学'],
            'transfer' => ['en' => 'Transfer admission', 'ko' => '편입학', 'zh_CN' => '插班入学'],
            'readmission' => ['en' => 'Readmission', 'ko' => '재입학', 'zh_CN' => '重新入学'],
            'international' => ['en' => 'International student admission', 'ko' => '외국인 특별전형', 'zh_CN' => '外国留学生入学'],
        ]);
        $this->lookupValues(Application::DOCUMENT_TYPE_LOOKUP, [
            'diploma' => ['en' => 'Diploma or certificate of expected graduation', 'ko' => '졸업(예정)증명서', 'zh_CN' => '毕业（预计毕业）证明'],
            'transcript' => ['en' => 'Transcript', 'ko' => '성적증명서', 'zh_CN' => '成绩证明'],
            'identity' => ['en' => 'Copy of ID or passport', 'ko' => '신분증 또는 여권 사본', 'zh_CN' => '身份证或护照复印件'],
            'language_test' => ['en' => 'Language test score', 'ko' => '어학 성적', 'zh_CN' => '语言成绩'],
        ]);
    }

    private function termTypes(): void
    {
        $types = [
            'spring' => [10, true, ['en' => 'Spring term', 'ko' => '1학기', 'zh_CN' => '春季学期']],
            'summer' => [20, false, ['en' => 'Summer session', 'ko' => '여름 계절학기', 'zh_CN' => '夏季学期']],
            'fall' => [30, true, ['en' => 'Fall term', 'ko' => '2학기', 'zh_CN' => '秋季学期']],
            'winter' => [40, false, ['en' => 'Winter session', 'ko' => '겨울 계절학기', 'zh_CN' => '冬季学期']],
        ];

        foreach ($types as $code => [$sortOrder, $isRegular, $name]) {
            $this->seedRow(TermType::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder, 'is_regular' => $isRegular]);
        }
    }

    private function courseCategories(): void
    {
        $groups = [
            'GE' => [10, ['en' => 'General education', 'ko' => '교양', 'zh_CN' => '通识教育'], [
                'GE_REQ' => ['en' => 'General education required', 'ko' => '교양필수', 'zh_CN' => '通识必修'],
                'GE_ELEC' => ['en' => 'General education elective', 'ko' => '교양선택', 'zh_CN' => '通识选修'],
            ]],
            'MAJ' => [20, ['en' => 'Major', 'ko' => '전공', 'zh_CN' => '专业'], [
                'MAJ_FND' => ['en' => 'Major foundation', 'ko' => '전공기초', 'zh_CN' => '专业基础'],
                'MAJ_REQ' => ['en' => 'Major required', 'ko' => '전공필수', 'zh_CN' => '专业必修'],
                'MAJ_ELEC' => ['en' => 'Major elective', 'ko' => '전공선택', 'zh_CN' => '专业选修'],
            ]],
            'FREE' => [30, ['en' => 'Free elective', 'ko' => '일반선택', 'zh_CN' => '任意选修'], []],
        ];

        foreach ($groups as $code => [$sortOrder, $name, $children]) {
            $parent = $this->seedRow(CourseCategory::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder]);
            $childOrder = $sortOrder;

            foreach ($children as $childCode => $childName) {
                $this->seedRow(CourseCategory::class, ['code' => $childCode], ['name' => $childName], ['parent_id' => $parent->id, 'sort_order' => ++$childOrder]);
            }
        }
    }

    /**
     * A standard course evaluation: five areas of three questions on a five-point scale
     * (the legacy areas), and one comment. Created once; admin edits are kept.
     */
    private function evaluationForm(): void
    {
        if (EvaluationForm::query()->where('code', 'STANDARD')->exists()) {
            return;
        }

        $form = EvaluationForm::query()->create([
            'code' => 'STANDARD',
            'name' => ['en' => 'Standard course evaluation', 'ko' => '표준 강의평가', 'zh_CN' => '标准课程评价'],
            'description' => [
                'en' => 'Your answers are anonymous: instructors see only totals, after the results are published, and only when enough students answered.',
                'ko' => '응답은 익명입니다. 교원은 결과 공개 후, 충분한 학생이 응답한 강좌의 집계만 봅니다.',
                'zh_CN' => '回答为匿名。教师只能在结果公布后、回答人数足够时看到汇总结果。',
            ],
            'scale_points' => 5,
            'scale_low_label' => ['en' => 'Strongly disagree', 'ko' => '전혀 그렇지 않다', 'zh_CN' => '完全不同意'],
            'scale_high_label' => ['en' => 'Strongly agree', 'ko' => '매우 그렇다', 'zh_CN' => '非常同意'],
        ]);

        $areas = [
            [['en' => 'Preparation', 'ko' => '강의 준비', 'zh_CN' => '课程准备'], [
                ['en' => 'The syllabus explained the goals, content and grading of the course.', 'ko' => '강의계획서에 수업 목표, 내용, 평가 방법이 잘 안내되었다.', 'zh_CN' => '教学大纲清楚说明了课程目标、内容和评分方法。'],
                ['en' => 'Classes were well prepared.', 'ko' => '수업이 충실히 준비되었다.', 'zh_CN' => '课程准备充分。'],
                ['en' => 'Classes started and ended on time.', 'ko' => '수업이 정해진 시간에 시작하고 끝났다.', 'zh_CN' => '课程按时开始和结束。'],
            ]],
            [['en' => 'Teaching', 'ko' => '강의 방법', 'zh_CN' => '教学方法'], [
                ['en' => 'Explanations were clear and easy to follow.', 'ko' => '설명이 명확하고 이해하기 쉬웠다.', 'zh_CN' => '讲解清楚易懂。'],
                ['en' => 'Students could ask questions and take part.', 'ko' => '질문과 참여의 기회가 충분했다.', 'zh_CN' => '学生有充分的提问和参与机会。'],
                ['en' => 'The pace of the course was hard to follow.', 'ko' => '수업 진도를 따라가기 어려웠다.', 'zh_CN' => '课程进度很难跟上。', 'reversed' => true],
            ]],
            [['en' => 'Content', 'ko' => '강의 내용', 'zh_CN' => '教学内容'], [
                ['en' => 'The content followed the syllabus.', 'ko' => '수업 내용이 강의계획서에 맞게 진행되었다.', 'zh_CN' => '教学内容与教学大纲一致。'],
                ['en' => 'The course increased my knowledge of the subject.', 'ko' => '이 과목에 대한 지식이 늘었다.', 'zh_CN' => '这门课增加了我对该学科的了解。'],
                ['en' => 'Materials and assignments helped me learn.', 'ko' => '교재와 과제가 학습에 도움이 되었다.', 'zh_CN' => '教材和作业对学习有帮助。'],
            ]],
            [['en' => 'Assessment', 'ko' => '평가 방법', 'zh_CN' => '考核方法'], [
                ['en' => 'Assessment followed the announced criteria.', 'ko' => '평가가 안내된 기준에 따라 이루어졌다.', 'zh_CN' => '考核按照公布的标准进行。'],
                ['en' => 'Exams and assignments matched what was taught.', 'ko' => '시험과 과제가 수업 내용과 맞았다.', 'zh_CN' => '考试和作业与教学内容相符。'],
                ['en' => 'I received useful feedback on my work.', 'ko' => '과제와 시험에 대해 도움이 되는 피드백을 받았다.', 'zh_CN' => '我得到了对学习有帮助的反馈。'],
            ]],
            [['en' => 'Facilities', 'ko' => '교학 시설 및 장비', 'zh_CN' => '教学设施和设备'], [
                ['en' => 'The classroom was suitable for the course.', 'ko' => '강의실이 수업에 적합했다.', 'zh_CN' => '教室适合本课程。'],
                ['en' => 'Equipment needed for the course worked well.', 'ko' => '수업에 필요한 장비가 잘 작동했다.', 'zh_CN' => '课程所需设备运行良好。'],
                ['en' => 'Overall, I am satisfied with this course.', 'ko' => '전반적으로 이 수업에 만족한다.', 'zh_CN' => '总体上我对这门课感到满意。'],
            ]],
        ];
        $order = 0;

        foreach ($areas as [$category, $questions]) {
            foreach ($questions as $question) {
                EvaluationQuestion::query()->create([
                    'evaluation_form_id' => $form->id,
                    'sort_order' => $order += 10,
                    'category' => $category,
                    'text' => array_diff_key($question, ['reversed' => true]),
                    'is_reversed' => $question['reversed'] ?? false,
                ]);
            }
        }

        EvaluationQuestion::query()->create([
            'evaluation_form_id' => $form->id,
            'sort_order' => $order + 10,
            'category' => ['en' => 'Comments', 'ko' => '의견', 'zh_CN' => '意见'],
            'text' => ['en' => 'What should the instructor keep or change?', 'ko' => '이 수업에서 좋았던 점이나 바뀌었으면 하는 점을 적어 주세요.', 'zh_CN' => '请写下这门课的优点或需要改进的地方。'],
            'question_type' => 'text',
            'is_required' => false,
        ]);
    }

    /**
     * The certificate kinds the legacy issued (재학, 졸업, 성적증명서) with their wording in
     * Korean, English and Chinese. Created once; the institution edits the templates.
     */
    private function certificateTypes(): void
    {
        if (CertificateType::query()->exists()) {
            return;
        }

        $kinds = [
            ['ENR', ['en' => 'Certificate of Enrollment', 'ko' => '재학증명서', 'zh_CN' => '在学证明书'], 'enrolled', false, [
                'ko' => ['재학증명서', "위의 사람이 본교에 재학 중임을 증명합니다.\n\n용도: {purpose}", '교무처장'],
                'en' => ['Certificate of Enrollment', "This is to certify that the above mentioned is presently enrolled at {institution}.\n\nPurpose: {purpose}", 'Dean of Academic Affairs'],
                'zh_CN' => ['在学证明书', "兹证明上述学生现在本校在读。\n\n用途：{purpose}", '教务处长'],
            ]],
            ['GRD', ['en' => 'Certificate of Graduation', 'ko' => '졸업증명서', 'zh_CN' => '毕业证明书'], 'graduated', false, [
                'ko' => ['졸업증명서', "위의 사람이 {graduation_date} 본교 {program}을(를) 졸업하였음을 증명합니다.\n\n학위번호: {degree_number}", '교무처장'],
                'en' => ['Certificate of Graduation', "This is to certify that the above mentioned graduated from {institution}, {program}, on {graduation_date}.\n\nDiploma no.: {degree_number}", 'Dean of Academic Affairs'],
                'zh_CN' => ['毕业证明书', "兹证明上述学生于{graduation_date}在本校{program}毕业。\n\n学位证书编号：{degree_number}", '教务处长'],
            ]],
            ['TRN', ['en' => 'Academic Transcript', 'ko' => '성적증명서', 'zh_CN' => '成绩证明书'], 'any', true, [
                'ko' => ['성적증명서', '위의 사람의 성적이 아래와 같음을 증명합니다.', '교무처장'],
                'en' => ['Academic Transcript', 'I certify that the transcript below is a complete and authentic copy of the academic record of the above mentioned.', 'Dean of Academic Affairs'],
                'zh_CN' => ['成绩证明书', '兹证明上述学生的成绩如下。', '教务处长'],
            ]],
        ];
        $order = 0;

        foreach ($kinds as [$code, $name, $audience, $grades, $templates]) {
            $type = CertificateType::query()->create(['code' => $code, 'name' => $name, 'audience' => $audience, 'has_grades' => $grades, 'sort_order' => $order += 10]);

            foreach ($templates as $locale => [$title, $body, $signer]) {
                CertificateTemplate::query()->create([
                    'certificate_type_id' => $type->id, 'locale' => $locale, 'title' => $title, 'body' => $body, 'signer' => $signer,
                    'footer' => ['ko' => '이 증명서의 진위는 아래 QR 코드 또는 확인 주소에서 확인할 수 있습니다.', 'en' => 'Verify this certificate with the QR code or at the address below.', 'zh_CN' => '可通过下方二维码或网址核验本证明。'][$locale],
                ]);
            }
        }
    }

    /**
     * The 4.5 scale, the institution's scale unless one is fixed already (the scale is fixed
     * at installation: setup:install --grade-scale, or the first profile's scale).
     */
    private function gradeScale(): void
    {
        self::seedScale45(true);
    }

    /**
     * The 4.5 scale (default only while the installation has no scale yet).
     */
    public static function seedScale45(bool $isDefault): GradeScale
    {
        // code => [points, min %, max %, counts in GPA, passing]
        return self::seedScale('KR45', ['en' => '4.5 scale', 'ko' => '4.5 만점', 'zh_CN' => '4.5 分制'], 4.5, $isDefault, [
            'A+' => [4.5, 95, 100, true, true],
            'A0' => [4.0, 90, 94.99, true, true],
            'B+' => [3.5, 85, 89.99, true, true],
            'B0' => [3.0, 80, 84.99, true, true],
            'C+' => [2.5, 75, 79.99, true, true],
            'C0' => [2.0, 70, 74.99, true, true],
            'D+' => [1.5, 65, 69.99, true, true],
            'D0' => [1.0, 60, 64.99, true, true],
            'F' => [0.0, 0, 59.99, true, false],
            'P' => [null, null, null, false, true],
            'NP' => [null, null, null, false, false],
        ]);
    }

    /**
     * The 4.3 scale with minus grades, for installations that choose it.
     */
    public static function seedScale43(bool $isDefault): GradeScale
    {
        return self::seedScale('KR43', ['en' => '4.3 scale', 'ko' => '4.3 만점', 'zh_CN' => '4.3 分制'], 4.3, $isDefault, self::scale43(['P' => [true], 'NP' => [false]]));
    }

    /**
     * 4.3 grades with three-point score ranges, plus the given non-GPA grades.
     *
     * @param  array<string, array{0: bool, 1?: string}>  $nonGpa  code => [passing, applies to]
     * @return array<string, array{0: float|null, 1: float|null, 2: float|null, 3: bool, 4: bool, 5?: string}>
     */
    public static function scale43(array $nonGpa): array
    {
        $items = [
            'A+' => [4.3, 97, 100], 'A0' => [4.0, 93, 96.99], 'A-' => [3.7, 90, 92.99],
            'B+' => [3.3, 87, 89.99], 'B0' => [3.0, 83, 86.99], 'B-' => [2.7, 80, 82.99],
            'C+' => [2.3, 77, 79.99], 'C0' => [2.0, 73, 76.99], 'C-' => [1.7, 70, 72.99],
            'D+' => [1.3, 67, 69.99], 'D0' => [1.0, 63, 66.99], 'D-' => [0.7, 60, 62.99],
            'F' => [0.0, 0, 59.99],
        ];
        $rows = [];

        foreach ($items as $code => [$points, $min, $max]) {
            $rows[$code] = [$points, $min, $max, true, $code !== 'F'];
        }

        foreach ($nonGpa as $code => $options) {
            $rows[$code] = [null, null, null, false, $options[0], $options[1] ?? 'pass_fail'];
        }

        return $rows;
    }

    /**
     * Creates the scale and its missing grades; existing grades and scales are not changed.
     * A new default is only set when no scale is the default yet (the scale is fixed).
     *
     * @param  array<string, string>  $name
     * @param  array<string, array{0: float|null, 1: float|null, 2: float|null, 3: bool, 4: bool, 5?: string}>  $items
     */
    public static function seedScale(string $code, array $name, float $maxPoints, bool $isDefault, array $items): GradeScale
    {
        $scale = GradeScale::query()->firstOrCreate(['code' => $code], [
            'name' => $name, 'max_grade_points' => $maxPoints, 'is_default' => $isDefault && ! GradeScale::query()->where('is_default', true)->exists(),
        ]);
        $order = 0;

        foreach ($items as $itemCode => $item) {
            [$points, $min, $max, $countsInGpa, $isPassing] = $item;

            GradeScaleItem::query()->firstOrCreate(['grade_scale_id' => $scale->id, 'code' => $itemCode], [
                'grade_points' => $points,
                'min_percentage' => $min,
                'max_percentage' => $max,
                'counts_in_gpa' => $countsInGpa,
                'is_passing' => $isPassing,
                'applies_to' => $item[5] ?? ($countsInGpa ? 'graded' : 'pass_fail'),
                'sort_order' => $order += 10,
            ]);
        }

        return $scale;
    }

    private function timeSlots(): void
    {
        foreach (range(1, 10) as $period) {
            $start = sprintf('%02d:00', 8 + $period);

            $this->seedRow(TimeSlot::class, ['code' => (string) $period], [
                'name' => ['en' => "Period {$period}", 'ko' => "{$period}교시", 'zh_CN' => "第{$period}节"],
            ], [
                'start_time' => $start,
                'end_time' => sprintf('%02d:50', 8 + $period),
                'weekdays' => [1, 2, 3, 4, 5],
                'sort_order' => $period,
            ]);
        }
    }

    /**
     * @param  array<string, array<string, string>>  $values
     */
    private function lookupValues(string $typeCode, array $values): void
    {
        $type = LookupType::query()->where('code', $typeCode)->sole();
        $order = 0;

        foreach ($values as $code => $name) {
            $this->seedRow(LookupValue::class, ['lookup_type_id' => $type->id, 'code' => $code], ['name' => $name], ['sort_order' => $order += 10]);
        }
    }

    private function roomTypes(): void
    {
        $type = LookupType::query()->where('code', Room::ROOM_TYPE_LOOKUP)->sole();
        $values = [
            'lecture' => ['en' => 'Lecture room', 'ko' => '강의실', 'zh_CN' => '教室'],
            'laboratory' => ['en' => 'Laboratory', 'ko' => '실험실', 'zh_CN' => '实验室'],
            'practice' => ['en' => 'Practice room', 'ko' => '실습실', 'zh_CN' => '实训室'],
            'seminar' => ['en' => 'Seminar room', 'ko' => '세미나실', 'zh_CN' => '研讨室'],
            'auditorium' => ['en' => 'Auditorium', 'ko' => '강당', 'zh_CN' => '礼堂'],
        ];
        $order = 0;

        foreach ($values as $code => $name) {
            $this->seedRow(LookupValue::class, ['lookup_type_id' => $type->id, 'code' => $code], ['name' => $name], ['sort_order' => $order += 10]);
        }
    }

    /**
     * Examination kinds (legacy CET types, SSCORE_ADD exams, COMEXAM): an in-house English
     * comprehensive exam with four areas, a computer exam and the thesis as pass/fail, and
     * TOPIK as an external test with a level.
     */
    private function assessmentTypes(): void
    {
        if (AssessmentType::query()->exists()) {
            return;
        }

        $english = AssessmentType::query()->create([
            'code' => 'ENG_COMP', 'name' => ['en' => 'English comprehensive examination', 'ko' => '영어종합시험', 'zh_CN' => '英语综合考试'],
            'category' => 'language', 'scoring' => 'score', 'pass_score' => 60, 'sort_order' => 10,
        ]);

        foreach ([
            ['LISTENING', ['en' => 'Listening', 'ko' => '듣기', 'zh_CN' => '听力']],
            ['READING', ['en' => 'Reading', 'ko' => '읽기', 'zh_CN' => '阅读']],
            ['WRITING', ['en' => 'Writing', 'ko' => '쓰기', 'zh_CN' => '写作']],
            ['GRAMMAR', ['en' => 'Grammar', 'ko' => '문법', 'zh_CN' => '语法']],
        ] as $index => [$code, $name]) {
            AssessmentComponent::query()->create(['assessment_type_id' => $english->id, 'code' => $code, 'name' => $name, 'max_score' => 25, 'sort_order' => ($index + 1) * 10]);
        }

        AssessmentType::query()->create([
            'code' => 'COMP_EXAM', 'name' => ['en' => 'Computer comprehensive examination', 'ko' => '전산종합시험', 'zh_CN' => '计算机综合考试'],
            'category' => 'comprehensive', 'scoring' => 'pass_fail', 'sort_order' => 20,
        ]);
        AssessmentType::query()->create([
            'code' => 'THESIS', 'name' => ['en' => 'Graduation thesis', 'ko' => '졸업논문', 'zh_CN' => '毕业论文'],
            'category' => 'thesis', 'scoring' => 'pass_fail', 'sort_order' => 30,
        ]);
        AssessmentType::query()->create([
            'code' => 'TOPIK', 'name' => ['en' => 'TOPIK (Test of Proficiency in Korean)', 'ko' => '한국어능력시험(TOPIK)', 'zh_CN' => '韩国语能力考试（TOPIK）'],
            'category' => 'external', 'scoring' => 'pass_fail', 'allows_external' => true, 'sort_order' => 40,
        ]);
    }
}
