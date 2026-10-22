<?php

namespace App\Domain\Security\Enums;

/**
 * Permission catalog. Permissions are defined in code and synchronized to the database
 * with `php artisan security:sync-permissions`. Roles are composed of these in the admin panel.
 *
 * Naming: {resource}.{action}, resource in snake_case plural (docs/conventions/naming.md).
 */
enum Permission: string
{
    case AdminPanelAccess = 'admin_panel.access';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersResetMfa = 'users.reset_mfa';

    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';
    case RoleAssignmentsManage = 'role_assignments.manage';

    case AuditRecordsView = 'audit_records.view';
    case SecurityEventsView = 'security_events.view';

    case SecuritySettingsManage = 'security_settings.manage';
    case InstitutionSettingsManage = 'institution_settings.manage';

    case QueueMonitorView = 'queue_monitor.view';

    case ReferenceDataView = 'reference_data.view';
    case ReferenceDataImport = 'reference_data.import';
    case LookupValuesManage = 'lookup_values.manage';
    case OrgUnitsManage = 'org_units.manage';
    case ProgramsManage = 'programs.manage';
    case FacilitiesManage = 'facilities.manage';
    case TermsManage = 'terms.manage';
    case TermWindowsManage = 'term_windows.manage';
    case CourseCategoriesManage = 'course_categories.manage';
    case GradeScalesManage = 'grade_scales.manage';

    case PeopleView = 'people.view';
    case PeopleManage = 'people.manage';
    case PeopleViewSensitive = 'people.view_sensitive';
    case PeopleMerge = 'people.merge';
    case AccountsInvite = 'accounts.invite';
    case StudentRecordsView = 'student_records.view';
    case StudentRecordsManage = 'student_records.manage';
    case StudentStatusChangesApprove = 'student_status_changes.approve';
    case InstructorsManage = 'instructors.manage';
    case AdvisorsManage = 'advisors.manage';
    case AuthenticationSettingsManage = 'authentication_settings.manage';

    case AdmissionCyclesManage = 'admission_cycles.manage';
    case ApplicationsView = 'applications.view';
    case ApplicationsReview = 'applications.review';
    case ApplicationsMatriculate = 'applications.matriculate';

    case CoursesManage = 'courses.manage';
    case CurriculumManage = 'curriculum.manage';
    case SectionsView = 'sections.view';
    case SectionsManage = 'sections.manage';
    case AdditionalProgramsApprove = 'additional_programs.approve';

    case EnrollmentsView = 'enrollments.view';
    case RegistrationManage = 'registration.manage';
    case RegistrationRulesManage = 'registration_rules.manage';
    case RegistrationHoldsManage = 'registration_holds.manage';

    public function resource(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    public function label(): string
    {
        return __('permissions.'.$this->value);
    }

    /**
     * @return array<string, list<self>>
     */
    public static function groupedByResource(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->resource()][] = $permission;
        }

        return $groups;
    }
}
