<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Offering\Section;
use App\Models\Term\TermSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Offers a course in a term session. The course version is the one in effect when the
 * session starts; the offering unit defaults to the course's unit.
 */
class CreateSection
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{section_code: string, capacity: int, org_unit_id?: int|null, eligibility?: mixed, exclusion?: mixed, instruction_language?: string|null, note?: mixed}  $data
     */
    public function handle(TermSession $session, Course $course, array $data, User $createdBy): Section
    {
        $orgUnitId = (int) ($data['org_unit_id'] ?? $course->org_unit_id);
        $scope = app(AuthorizationService::class)->scopeFor($createdBy, Permission::SectionsManage);

        if (! $scope->isGlobal && ! in_array($orgUnitId, $scope->orgUnitIds, true)) {
            throw new AuthorizationException;
        }

        $version = self::versionFor($course, $session);

        try {
            $section = DB::transaction(fn () => Section::query()->create([
                ...Arr::only($data, ['section_code', 'capacity', 'eligibility', 'exclusion', 'instruction_language', 'note']),
                'term_id' => $session->term_id,
                'term_session_id' => $session->id,
                'course_id' => $course->id,
                'course_version_id' => $version->id,
                'org_unit_id' => $orgUnitId,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['section_code' => __('offering.errors.section_code_taken')]);
        }

        $this->audit->record('section.created', $section, null, ['course' => $course->code, 'term_session_id' => $session->id], $createdBy);

        return $section;
    }

    public static function versionFor(Course $course, TermSession $session): CourseVersion
    {
        return $course->versionOn($session->start_date)
            ?? throw ValidationException::withMessages(['course_id' => __('offering.errors.no_course_version', ['course' => $course->code, 'date' => $session->start_date->toDateString()])]);
    }
}
