<?php

namespace Database\Seeders\Profiles;

use App\Models\Admission\Application;
use App\Models\Curriculum\CourseCategory;
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

    private function gradeScale(): void
    {
        $scale = $this->seedRow(GradeScale::class, ['code' => 'KR45'], [
            'name' => ['en' => '4.5 scale', 'ko' => '4.5 만점', 'zh_CN' => '4.5 分制'],
        ], [
            'max_grade_points' => 4.5,
            'is_default' => ! GradeScale::query()->where('is_default', true)->exists(),
        ]);

        // code => [points, min %, max %, counts in GPA, passing]
        $items = [
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
        ];

        $order = 0;

        foreach ($items as $code => [$points, $min, $max, $countsInGpa, $isPassing]) {
            GradeScaleItem::query()->firstOrCreate(['grade_scale_id' => $scale->id, 'code' => $code], [
                'grade_points' => $points,
                'min_percentage' => $min,
                'max_percentage' => $max,
                'counts_in_gpa' => $countsInGpa,
                'is_passing' => $isPassing,
                'sort_order' => $order += 10,
            ]);
        }
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
}
