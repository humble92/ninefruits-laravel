<?php

namespace Database\Seeders\Demo;

use App\Domain\Organization\Enums\DegreeLevel;
use App\Domain\Organization\Enums\OrgUnitChangeType;
use App\Domain\Term\Enums\TermStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitSuccession;
use App\Models\Organization\OrgUnitType;
use App\Models\Organization\Program;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\Term\TermWindow;
use App\Support\Setup\DemoOptions;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Illuminate\Database\Seeder;

/**
 * Org units, programs, buildings, rooms, academic years and terms around today: the last
 * term has closed grades, the current one is in session, the next one has registration
 * opening by year level (porting/porting-plan.md section 13.4). Runs once per database.
 */
class DemoReferenceSeeder extends Seeder
{
    public function run(DemoOptions $options): void
    {
        if (OrgUnit::query()->where('code', 'DEMO')->exists()) {
            return;
        }

        $this->call(KoreanUniversityProfileSeeder::class);

        $this->organization();
        $this->facilities($options);
        $this->terms();
    }

    private function organization(): void
    {
        $types = OrgUnitType::query()->pluck('id', 'code');
        $unit = fn (string $code, array $name, string $type, ?OrgUnit $parent = null, string $start = '1990-03-01', ?string $end = null) => OrgUnit::query()->create([
            'code' => $code,
            'name' => $name,
            'org_unit_type_id' => $types[$type],
            'parent_id' => $parent?->id,
            'effective_start_date' => $start,
            'effective_end_date' => $end,
        ]);

        $university = $unit('DEMO', ['en' => 'Demo University', 'ko' => '데모대학교', 'zh_CN' => '演示大学'], 'university');

        $structure = [
            'ENG' => [['en' => 'College of Engineering', 'ko' => '공과대학', 'zh_CN' => '工学院'], [
                'CSE' => ['en' => 'Computer Science and Engineering', 'ko' => '컴퓨터공학과', 'zh_CN' => '计算机工程系'],
                'EE' => ['en' => 'Electrical Engineering', 'ko' => '전기공학과', 'zh_CN' => '电气工程系'],
            ]],
            'HUM' => [['en' => 'College of Humanities', 'ko' => '인문대학', 'zh_CN' => '人文学院'], [
                'KOR' => ['en' => 'Korean Language and Literature', 'ko' => '국어국문학과', 'zh_CN' => '韩国语言文学系'],
                'CHN' => ['en' => 'Chinese Language and Literature', 'ko' => '중어중문학과', 'zh_CN' => '汉语言文学系'],
            ]],
            'NUR' => [['en' => 'College of Nursing', 'ko' => '간호대학', 'zh_CN' => '护理学院'], [
                'NURS' => ['en' => 'Nursing', 'ko' => '간호학과', 'zh_CN' => '护理学系'],
            ]],
        ];

        foreach ($structure as $collegeCode => [$collegeName, $departments]) {
            $college = $unit($collegeCode, $collegeName, 'college', $university);

            foreach ($departments as $code => $name) {
                $department = $unit($code, $name, 'department', $college);

                Program::query()->create([
                    'code' => "B-{$code}",
                    'name' => ['en' => 'Bachelor of '.$name['en'], 'ko' => $name['ko'].' 학사과정', 'zh_CN' => $name['zh_CN'].'本科'],
                    'degree_level' => DegreeLevel::Bachelor,
                    'org_unit_id' => $department->id,
                    'standard_duration_years' => 4,
                    'effective_start_date' => '1990-03-01',
                ]);
            }
        }

        // History example: two departments merged into Computer Science and Engineering.
        $computerScience = OrgUnit::query()->where('code', 'CSE')->sole();
        $old = $unit('CS-OLD', ['en' => 'Computer Science (until 2015)', 'ko' => '전산학과 (2015년까지)', 'zh_CN' => '计算机科学系 (至2015年)'], 'department', OrgUnit::query()->where('code', 'ENG')->sole(), '1990-03-01', '2015-02-28');

        OrgUnitSuccession::query()->create([
            'predecessor_org_unit_id' => $old->id,
            'successor_org_unit_id' => $computerScience->id,
            'change_type' => OrgUnitChangeType::Merged,
            'effective_date' => '2015-03-01',
            'reason' => 'Demo: department merger',
        ]);
    }

    private function facilities(DemoOptions $options): void
    {
        $roomTypes = LookupValue::query()->ofType(Room::ROOM_TYPE_LOOKUP)->pluck('id', 'code');
        $buildings = [
            'SCI' => ['en' => 'Science Hall', 'ko' => '과학관', 'zh_CN' => '科学楼'],
            'HUM' => ['en' => 'Humanities Hall', 'ko' => '인문관', 'zh_CN' => '人文楼'],
            'NUR' => ['en' => 'Nursing Hall', 'ko' => '간호관', 'zh_CN' => '护理楼'],
        ];

        foreach ($buildings as $code => $name) {
            $building = Building::query()->create(['code' => $code, 'name' => $name]);

            foreach (range(1, $options->scale(8)) as $index) {
                $floor = intdiv($index - 1, 8) + 1;
                $type = $index % 5 === 0 ? 'laboratory' : 'lecture';

                Room::query()->create([
                    'building_id' => $building->id,
                    'code' => sprintf('%d%02d', $floor, ($index - 1) % 8 + 1),
                    'room_type_id' => $roomTypes[$type] ?? null,
                    'capacity' => $type === 'laboratory' ? 30 : [40, 60, 80][$index % 3],
                ]);
            }
        }
    }

    private function terms(): void
    {
        $today = CarbonImmutable::today();
        $types = TermType::query()->pluck('id', 'code');

        // Regular terms around today: spring Mar 2 - Jun 20, fall Sep 1 - Dec 20.
        $candidates = [];

        foreach (range($today->year - 1, $today->year + 1) as $year) {
            $candidates[] = ['spring', CarbonImmutable::create($year, 3, 2), CarbonImmutable::create($year, 6, 20)];
            $candidates[] = ['fall', CarbonImmutable::create($year, 9, 1), CarbonImmutable::create($year, 12, 20)];
        }

        $currentIndex = collect($candidates)->filter(fn (array $term) => $term[1]->lessThanOrEqualTo($today))->keys()->last();
        $chosen = [
            'past' => $candidates[$currentIndex - 1],
            'current' => $candidates[$currentIndex],
            'next' => $candidates[$currentIndex + 1],
        ];

        $terms = [];

        foreach ($chosen as $role => [$type, $start, $end]) {
            $year = $start->month >= 3 ? $start->year : $start->year - 1;
            $academicYear = AcademicYear::query()->firstOrCreate(['code' => (string) $year], [
                'name' => ['en' => "{$year} academic year", 'ko' => "{$year}학년도", 'zh_CN' => "{$year}学年"],
                'start_date' => "{$year}-03-01",
                'end_date' => CarbonImmutable::create($year + 1, 3, 1)->subDay(),
            ]);

            $label = $type === 'spring' ? ['en' => 'Spring', 'ko' => '1학기', 'zh_CN' => '春季学期'] : ['en' => 'Fall', 'ko' => '2학기', 'zh_CN' => '秋季学期'];

            $term = Term::query()->create([
                'academic_year_id' => $academicYear->id,
                'term_type_id' => $types[$type],
                'code' => $year.'-'.($type === 'spring' ? 'SP' : 'FA'),
                'name' => ['en' => "{$label['en']} {$year}", 'ko' => "{$year}학년도 {$label['ko']}", 'zh_CN' => "{$year}学年{$label['zh_CN']}"],
                'start_date' => $start,
                'end_date' => $end,
            ]);
            $term->forceFill(['status' => $role === 'past' ? TermStatus::GradesClosed : TermStatus::Published])->save();
            $terms[$role] = $term;
        }

        $now = CarbonImmutable::now()->startOfHour();
        $window = fn (Term $term, TermWindowType $type, CarbonImmutable $opens, CarbonImmutable $closes, ?TargetCriteria $criteria = null, ?array $name = null) => TermWindow::query()->create([
            'term_id' => $term->id,
            'window_type' => $type,
            'name' => $name,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'eligibility' => $criteria ?? TargetCriteria::everyone(),
        ]);

        $past = $terms['past'];
        $window($past, TermWindowType::GradeEntry, $past->end_date->subDays(7)->setTime(9, 0), $past->end_date->addDays(7)->setTime(18, 0));

        $current = $terms['current'];
        $window($current, TermWindowType::AddDrop, $current->start_date->setTime(9, 0), $current->start_date->addDays(7)->setTime(18, 0));
        $window($current, TermWindowType::CourseEvaluation, $current->end_date->subDays(21)->setTime(9, 0), $current->end_date->setTime(18, 0));
        $window($current, TermWindowType::GradeEntry, $current->end_date->subDays(7)->setTime(9, 0), $current->end_date->addDays(7)->setTime(18, 0));

        $next = $terms['next'];
        $window($next, TermWindowType::RegistrationCart, $now->subDays(3), $now->addDays(7));
        $window($next, TermWindowType::Registration, $now->subDay(), $now->addDays(5), new TargetCriteria(yearLevels: [4]), ['en' => 'Fourth year', 'ko' => '4학년', 'zh_CN' => '四年级']);
        $window($next, TermWindowType::Registration, $now->addDay(), $now->addDays(5), new TargetCriteria(yearLevels: [3]), ['en' => 'Third year', 'ko' => '3학년', 'zh_CN' => '三年级']);
        $window($next, TermWindowType::Registration, $now->addDays(2), $now->addDays(5), new TargetCriteria(yearLevels: [1, 2]), ['en' => 'First and second year', 'ko' => '1, 2학년', 'zh_CN' => '一、二年级']);
    }
}
