<?php

namespace App\Domain\Attendance\Services;

use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Reporting\Services\ReportCatalog;
use App\Domain\Security\Enums\Permission;
use App\Models\Offering\Section;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

/**
 * The students of a section for its instructors and the staff who see the section:
 * enrolled and completed enrollments with the person, program and unit.
 */
class SectionRoster
{
    /**
     * @return Collection<int, Enrollment>
     */
    public function enrollments(Section $section): Collection
    {
        return Enrollment::query()->where('section_id', $section->id)
            ->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
            ->with(['studentRecord.person', 'studentRecord.program', 'studentRecord.orgUnit'])
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->studentRecord->student_number)->values();
    }

    public static function canView(Section $section, User $user): bool
    {
        return $section->isTaughtBy($user)
            || $section->isInScopeOf($user, Permission::SectionsView)
            || $section->isInScopeOf($user, Permission::SectionsManage);
    }

    public static function authorizeView(Section $section, User $user): void
    {
        if (! self::canView($section, $user)) {
            throw new AuthorizationException;
        }
    }

    /**
     * UTF-8 CSV with a byte order mark so that spreadsheets open it correctly.
     */
    public function csv(Section $section): string
    {
        $rows = [[__('people.fields.student_number'), __('people.fields.name'), __('reference.resources.programs.label'), __('people.fields.year_level'), __('people.fields.contact_email'), __('registration.fields.status')]];

        foreach ($this->enrollments($section) as $enrollment) {
            $record = $enrollment->studentRecord;
            $rows[] = array_map([ReportCatalog::class, 'cell'], [$record->student_number, $record->person->displayName(), $record->program->name, (string) $record->year_level, (string) ($record->person->email ?? ''), $enrollment->status->label()]);
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
