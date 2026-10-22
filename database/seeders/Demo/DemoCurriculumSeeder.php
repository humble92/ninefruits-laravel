<?php

namespace Database\Seeders\Demo;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Curriculum\Enums\RequisiteKind;
use App\Domain\Offering\Actions\CopySections;
use App\Domain\Offering\Enums\InstructorRole;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Person\Enums\AdditionalProgramRequestStatus;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Term\Actions\CopySessionWindows;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CourseEquivalency;
use App\Models\Curriculum\CourseRequisite;
use App\Models\Curriculum\CourseVersion;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Facility\Room;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Offering\SectionMeeting;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Offering\Syllabus;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\Program;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentRecordProgram;
use App\Models\Term\Term;
use App\Models\Term\TermSession;
use App\Models\Term\TermWindow;
use App\Models\User;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Courses (with a revised course and an equivalency), requisites, graduation requirements
 * per program and role, first and second half sessions in the current term, sections with
 * instructors, rooms and syllabi, the next term copied from the current one, and second
 * major and minor applications.
 */
class DemoCurriculumSeeder extends Seeder
{
    /** Course titles per level: [en, ko, zh_CN] with {name} for the department. */
    private const array TOPICS = [
        1 => [['Introduction to {name}', '{name} 개론', '{name}导论'], ['Foundations of {name}', '{name} 기초', '{name}基础']],
        2 => [['Methods in {name}', '{name} 방법론', '{name}方法论'], ['{name} Laboratory', '{name} 실습', '{name}实验']],
        3 => [['Topics in {name}', '{name} 특강', '{name}专题'], ['Seminar in {name}', '{name} 세미나', '{name}研讨']],
        4 => [['Advanced {name}', '고급 {name}', '高级{name}'], ['Capstone Project in {name}', '{name} 캡스톤 프로젝트', '{name}综合设计']],
    ];

    /** Meeting patterns (weekdays) and start times; 75 minutes each. */
    private const array PATTERNS = [[1, 3], [2, 4], [1, 3], [2, 4], [5]];

    private const array STARTS = ['09:00', '10:30', '13:00', '14:30', '16:00'];

    /** @var array<string, true> Room and instructor slots taken, per session code */
    private array $taken = [];

    public function run(): void
    {
        if (Course::query()->where('code', 'GEN1001')->exists()) {
            return;
        }

        $current = Term::query()->where('status', 'published')->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();
        $next = Term::query()->where('status', 'published')->where('start_date', '>', now()->toDateString())->orderBy('start_date')->first();

        if ($current === null || $next === null) {
            return;
        }

        $categories = CourseCategory::query()->pluck('id', 'code');
        $courses = $this->courses($categories);
        $this->requirements($courses, $categories);

        $this->sessions($current);
        $this->sections($current, $courses);

        // The next term starts as a copy of the current one, as staff would do it.
        $planner = User::query()->where('email', 'academic-affairs@'.config('setup.demo.email_domain'))->first();

        if ($planner !== null) {
            app(CopySections::class)->handle($current, $next, $planner);
            Section::query()->where('term_id', $next->id)->update(['status' => SectionStatus::Published]);
        }

        $this->additionalPrograms($next);
    }

    /**
     * @param  Collection<string, int>  $categories
     * @return array<string, list<Course>> Courses by unit code ('GEN' for general education)
     */
    private function courses(Collection $categories): array
    {
        $byUnit = [];
        $university = OrgUnit::query()->where('code', 'DEMO')->sole();
        $general = [
            ['GEN1001', 'GE_REQ', ['en' => 'Academic Writing', 'ko' => '대학 글쓰기', 'zh_CN' => '学术写作']],
            ['GEN1002', 'GE_REQ', ['en' => 'English Communication', 'ko' => '영어 의사소통', 'zh_CN' => '英语交流']],
            ['GEN1003', 'GE_REQ', ['en' => 'Computing for Everyone', 'ko' => '모두를 위한 컴퓨팅', 'zh_CN' => '大众计算']],
            ['GEN2001', 'GE_ELEC', ['en' => 'Introduction to Statistics', 'ko' => '통계학 입문', 'zh_CN' => '统计学入门']],
            ['GEN2002', 'GE_ELEC', ['en' => 'History of Science', 'ko' => '과학사', 'zh_CN' => '科学史']],
            ['GEN2003', 'GE_ELEC', ['en' => 'Philosophy and Ethics', 'ko' => '철학과 윤리', 'zh_CN' => '哲学与伦理']],
        ];

        foreach ($general as [$code, $category, $title]) {
            $byUnit['GEN'][] = $this->course($code, $university, $title, 3, $categories[$category] ?? null);
        }

        foreach (Program::query()->effectiveOn()->with('orgUnit')->orderBy('code')->get() as $program) {
            $unit = $program->orgUnit;
            $names = $unit->getTranslations('name');

            foreach (self::TOPICS as $level => $topics) {
                foreach ($topics as $index => [$en, $ko, $zh]) {
                    $title = [
                        'en' => str_replace('{name}', $names['en'] ?? $unit->code, $en),
                        'ko' => str_replace('{name}', $names['ko'] ?? $unit->code, $ko),
                        'zh_CN' => str_replace('{name}', $names['zh_CN'] ?? $unit->code, $zh),
                    ];
                    $category = $level === 1 ? 'MAJ_FND' : ($level <= 2 ? 'MAJ_REQ' : 'MAJ_ELEC');
                    $byUnit[$unit->code][] = $this->course(sprintf('%s%d%03d', $unit->code, $level, $index + 1), $unit, $title, $index === 1 && $level === 2 ? 2 : 3, $categories[$category] ?? null);
                }
            }

            // Second majors and minors in every other program.
            $program->update(['allows_second_major' => $program->id % 2 === 0, 'allows_minor' => true]);
        }

        // A revision: the first department course went from 3 to 4 credits two years ago, and
        // an older course it replaced counts as equivalent.
        $first = collect($byUnit)->except('GEN')->first()[0] ?? null;

        if ($first !== null) {
            $revisedFrom = CarbonImmutable::create(now()->year - 2, 3, 1);
            $version = $first->versions()->sole();
            $version->update(['effective_end_date' => $revisedFrom->subDay()]);
            CourseVersion::query()->create([
                ...$version->only(['lecture_hours', 'lab_hours', 'course_category_id']),
                'title' => $version->getTranslations('title'),
                'course_id' => $first->id,
                'effective_start_date' => $revisedFrom,
                'credits' => 4,
            ]);

            $old = $this->course($first->code.'X', $first->orgUnit, ['en' => 'Old '.$version->getTranslation('title', 'en'), 'ko' => '(구) '.$version->getTranslation('title', 'ko')], 3, $version->course_category_id, '2010-03-01', '2019-02-28');
            $old->update(['is_active' => false]);
            CourseEquivalency::query()->create(['course_id' => $first->id, 'equivalent_course_id' => $old->id, 'effective_start_date' => '2019-03-01']);
        }

        // Level 2 needs the first level 1 course; level 3 needs one of the level 2 courses.
        foreach (collect($byUnit)->except('GEN') as $unitCourses) {
            foreach ($unitCourses as $course) {
                $level = (int) substr($course->code, -4, 1);
                $required = match ($level) {
                    2 => [$unitCourses[0]],
                    3 => [$unitCourses[2], $unitCourses[3]],
                    default => [],
                };

                foreach ($required as $requiredCourse) {
                    foreach ($course->versions()->get() as $courseVersion) {
                        CourseRequisite::query()->create([
                            'course_version_id' => $courseVersion->id,
                            'kind' => RequisiteKind::Prerequisite,
                            'required_course_id' => $requiredCourse->id,
                        ]);
                    }
                }
            }
        }

        return $byUnit;
    }

    /**
     * @param  array<string, string>  $title
     */
    private function course(string $code, OrgUnit $unit, array $title, int $credits, ?int $categoryId, string $from = '2000-03-01', ?string $to = null): Course
    {
        $course = Course::query()->create(['code' => $code, 'org_unit_id' => $unit->id]);
        CourseVersion::query()->create([
            'course_id' => $course->id,
            'effective_start_date' => $from,
            'effective_end_date' => $to,
            'title' => $title,
            'credits' => $credits,
            'lecture_hours' => $credits,
            'course_category_id' => $categoryId,
        ]);

        return $course;
    }

    /**
     * @param  array<string, list<Course>>  $courses
     * @param  Collection<string, int>  $categories
     */
    private function requirements(array $courses, Collection $categories): void
    {
        foreach (Program::query()->effectiveOn()->with('orgUnit')->get() as $program) {
            $unitCourses = collect($courses[$program->orgUnit->code] ?? []);
            $roles = [ProgramRole::Primary, ...($program->allows_second_major ? [ProgramRole::SecondMajor] : []), ...($program->allows_minor ? [ProgramRole::Minor] : [])];

            foreach ($roles as $role) {
                $set = CurriculumRequirementSet::query()->create([
                    'program_id' => $program->id,
                    'program_role' => $role,
                    'catalog_year_from' => 2015,
                    'name' => ['en' => $role->label().' requirements from 2015', 'ko' => '2015 이후 '.__('curriculum.program_roles.'.$role->value, [], 'ko').' 요건'],
                    'total_credits' => match ($role) {
                        ProgramRole::Primary => 130,
                        ProgramRole::SecondMajor => 36,
                        ProgramRole::Minor => 21,
                    },
                ]);

                $line = fn (RequirementKind $kind, array $attributes, array $courseIds = []) => tap(
                    CurriculumRequirement::query()->create(['curriculum_requirement_set_id' => $set->id, 'kind' => $kind, ...$attributes]),
                    fn (CurriculumRequirement $requirement) => $requirement->courses()->attach($courseIds),
                );

                if ($role === ProgramRole::Primary) {
                    $line(RequirementKind::CategoryCredits, ['course_category_id' => $categories['GE_REQ'] ?? null, 'min_credits' => 9, 'sort_order' => 1]);
                    $line(RequirementKind::CategoryCredits, ['course_category_id' => $categories['GE_ELEC'] ?? null, 'min_credits' => 15, 'sort_order' => 2]);
                }

                if ($role !== ProgramRole::Minor) {
                    $line(RequirementKind::RequiredCourses, ['sort_order' => 3], $unitCourses->take(4)->pluck('id')->all());
                }

                $line(RequirementKind::CourseGroup, ['min_credits' => $role === ProgramRole::Minor ? 21 : 12, 'sort_order' => 4], $unitCourses->slice($role === ProgramRole::Minor ? 0 : 4)->pluck('id')->all());
            }
        }
    }

    private function sessions(Term $term): void
    {
        $full = $term->fullTermSession();
        $middle = $term->start_date->addDays(intdiv((int) $term->start_date->diffInDays($term->end_date), 2));
        $first = TermSession::query()->create(['term_id' => $term->id, 'code' => 'A', 'name' => ['en' => 'First half', 'ko' => '전반기', 'zh_CN' => '前半学期'], 'start_date' => $term->start_date, 'end_date' => $middle, 'sort_order' => 1]);
        $second = TermSession::query()->create(['term_id' => $term->id, 'code' => 'B', 'name' => ['en' => 'Second half', 'ko' => '후반기', 'zh_CN' => '后半学期'], 'start_date' => $middle->addDay(), 'end_date' => $term->end_date, 'sort_order' => 2]);

        $planner = User::query()->where('email', 'academic-affairs@'.config('setup.demo.email_domain'))->first();

        if ($planner !== null) {
            app(CopySessionWindows::class)->handle($full, $first, $planner);
            app(CopySessionWindows::class)->handle($full, $second, $planner);
        }
    }

    /**
     * @param  array<string, list<Course>>  $courses
     */
    private function sections(Term $term, array $courses): void
    {
        $sessions = $term->sessions()->get()->keyBy('code');
        $rooms = Room::query()->where('is_active', true)->where('is_schedulable', true)->orderBy('capacity')->orderBy('id')->get();
        $instructors = Instructor::query()->current()->get()->groupBy('org_unit_id');
        $demoInstructor = User::query()->where('email', 'instructor@'.config('setup.demo.email_domain'))->value('person_id');
        $index = 0;

        foreach ($courses as $unitCode => $unitCourses) {
            foreach ($unitCourses as $course) {
                if (! $course->is_active) {
                    continue;
                }

                $copies = $unitCode === 'GEN' ? 2 : 1;

                foreach (range(1, $copies) as $copy) {
                    $index++;
                    // Every seventh department section runs in one half of the term.
                    $session = $unitCode !== 'GEN' && $index % 7 === 0 ? $sessions[$index % 2 === 0 ? 'A' : 'B'] : $sessions['FULL'];
                    $version = $course->versionOn($session->start_date);

                    if ($version === null) {
                        continue;
                    }

                    $section = new Section([
                        'term_id' => $term->id,
                        'term_session_id' => $session->id,
                        'course_id' => $course->id,
                        'course_version_id' => $version->id,
                        'section_code' => sprintf('%02d', $copy),
                        'org_unit_id' => $course->org_unit_id,
                        'capacity' => $unitCode === 'GEN' ? 60 : 40,
                    ]);
                    $section->forceFill(['status' => SectionStatus::Published])->save();

                    $instructor = $unitCode === 'GEN'
                        ? $instructors->flatten()->get($index % max(1, $instructors->flatten()->count()))
                        : $instructors->get($course->org_unit_id)?->get($index % max(1, $instructors->get($course->org_unit_id)?->count() ?? 1));

                    $this->schedule($section, $session, $rooms, $instructor, $index);

                    if ($unitCode !== 'GEN' && $index % 4 === 0) {
                        SectionSeatGroup::query()->create([
                            'section_id' => $section->id,
                            'name' => ['en' => 'Majors', 'ko' => '전공 학생', 'zh_CN' => '本专业学生'],
                            'capacity' => 30,
                            'eligibility' => new TargetCriteria(orgUnitIds: [$course->org_unit_id]),
                            'releases_at' => $term->start_date->subDays(3)->setTime(9, 0),
                        ]);
                    }

                    if ($instructor !== null && ($instructor->person_id === $demoInstructor || $index % 3 === 0)) {
                        $this->syllabus($section, $version);
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, Room>  $rooms
     */
    private function schedule(Section $section, TermSession $session, Collection $rooms, ?Instructor $instructor, int $seed): void
    {
        $candidates = [];

        foreach (self::PATTERNS as $pattern) {
            foreach (self::STARTS as $start) {
                $candidates[] = [$pattern, $start];
            }
        }

        $offset = $seed % count($candidates);
        $candidates = [...array_slice($candidates, $offset), ...array_slice($candidates, 0, $offset)];
        $sessionKey = $session->is_full_term ? ['FULL', 'A', 'B'] : [$session->code, 'FULL'];

        foreach ($candidates as [$days, $start]) {
            $instructorFree = $instructor === null || ! $this->isTaken('i'.$instructor->id, $days, $start, $sessionKey);
            $room = $rooms->first(fn (Room $room) => ($room->capacity ?? 0) >= $section->capacity && ! $this->isTaken('r'.$room->id, $days, $start, $sessionKey));

            if (! $instructorFree || $room === null) {
                continue;
            }

            $end = CarbonImmutable::parse('2000-01-01 '.$start)->addMinutes(75)->format('H:i');

            if ($instructor !== null) {
                SectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => $instructor->id, 'role' => InstructorRole::Primary]);
            }

            foreach ($days as $day) {
                SectionMeeting::query()->create([
                    'section_id' => $section->id,
                    'weekday' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'room_id' => $room->id,
                    'start_date' => $session->start_date,
                    'end_date' => $session->end_date,
                ]);
                $this->take('r'.$room->id, $day, $start, $session->code);

                if ($instructor !== null) {
                    $this->take('i'.$instructor->id, $day, $start, $session->code);
                }
            }

            return;
        }
    }

    /**
     * @param  list<int>  $days
     * @param  list<string>  $sessions  Sessions whose dates overlap
     */
    private function isTaken(string $resource, array $days, string $start, array $sessions): bool
    {
        foreach ($days as $day) {
            foreach ($sessions as $session) {
                if (isset($this->taken["{$resource}|{$day}|{$start}|{$session}"])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function take(string $resource, int $day, string $start, string $session): void
    {
        $this->taken["{$resource}|{$day}|{$start}|{$session}"] = true;
    }

    private function syllabus(Section $section, CourseVersion $version): void
    {
        $syllabus = Syllabus::query()->create([
            'section_id' => $section->id,
            'language' => 'ko',
            'content' => [
                'overview' => $version->getTranslation('title', 'ko').' 과목의 핵심 개념을 다룬다.',
                'objectives' => "기본 개념을 설명할 수 있다.\n과제를 통해 개념을 적용할 수 있다.",
                'materials' => '강의 자료는 수업 중 배포한다.',
                'policies' => '출석은 매 수업 확인한다.',
                'assessment' => [['item' => '중간고사', 'weight' => 30], ['item' => '기말고사', 'weight' => 40], ['item' => '과제', 'weight' => 20], ['item' => '출석', 'weight' => 10]],
                'weekly_plan' => array_map(fn (int $week) => ['week' => $week, 'topic' => $week === 8 ? '중간고사' : ($week === 16 ? '기말고사' : "{$week}주차 주제")], range(1, 16)),
            ],
        ]);
        $syllabus->forceFill(['published_at' => now()])->save();
    }

    private function additionalPrograms(Term $next): void
    {
        TermWindow::query()->create([
            'term_id' => $next->id,
            'window_type' => TermWindowType::AdditionalProgramApplication,
            'name' => ['en' => 'Second major and minor applications', 'ko' => '복수전공·부전공 신청', 'zh_CN' => '第二专业与辅修申请'],
            'opens_at' => now()->subDays(2)->startOfHour(),
            'closes_at' => now()->addDays(12)->startOfHour(),
            'eligibility' => new TargetCriteria(yearLevels: [2, 3]),
        ]);

        $targets = Program::query()->effectiveOn()->where('allows_minor', true)->get();
        $students = StudentRecord::query()->active()->whereIn('year_level', [2, 3])
            ->whereDoesntHave('person.user', fn ($user) => $user->where('email', 'like', '%@'.config('setup.demo.email_domain')))
            ->orderBy('id')->limit(6)->get();

        foreach ($students as $index => $record) {
            $program = $targets->first(fn (Program $program) => $program->id !== $record->program_id);

            if ($program === null) {
                continue;
            }

            $role = $program->allows_second_major && $index % 2 === 0 ? AdditionalProgramRole::SecondMajor : AdditionalProgramRole::Minor;
            $request = AdditionalProgramRequest::query()->create([
                'student_record_id' => $record->id,
                'program_id' => $program->id,
                'org_unit_id' => $program->org_unit_id,
                'role' => $role,
                'term_id' => $next->id,
                'statement' => 'Interested in combining both fields.',
                'requested_at' => now()->subDays(1),
            ]);

            // The first two were approved earlier.
            if ($index < 2) {
                $added = StudentRecordProgram::query()->create(['student_record_id' => $record->id, 'program_id' => $program->id, 'role' => $role, 'start_date' => $next->start_date]);
                $request->forceFill(['status' => AdditionalProgramRequestStatus::Approved, 'decided_at' => now(), 'student_record_program_id' => $added->id])->save();
            }
        }
    }
}
