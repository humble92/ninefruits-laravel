<?php

namespace Database\Seeders\Essential;

use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Models\Security\Role;
use Illuminate\Database\Seeder;

/**
 * Default roles based on the legacy Oracle roles (docs/porting-plan.md section 7.2).
 * Safe to run repeatedly: existing roles keep the display names and permissions
 * administrators gave them. System roles always get every permission.
 */
class RoleTemplateSeeder extends Seeder
{
    public function run(SyncPermissionCatalog $syncPermissionCatalog): void
    {
        $staff = [Permission::AdminPanelAccess, Permission::UsersView, Permission::ReferenceDataView];

        $templates = [
            'system_administrator' => [
                'display_name' => ['en' => 'System Administrator', 'ko' => '시스템 관리자', 'zh_CN' => '系统管理员'],
                'is_system' => true,
                'permissions' => Permission::cases(),
            ],
            'registrar' => [
                'display_name' => ['en' => 'Registrar', 'ko' => '학적 담당', 'zh_CN' => '学籍管理员'],
                'permissions' => [
                    ...$staff,
                    Permission::PeopleView,
                    Permission::PeopleManage,
                    Permission::PeopleViewSensitive,
                    Permission::PeopleMerge,
                    Permission::AccountsInvite,
                    Permission::StudentRecordsView,
                    Permission::StudentRecordsManage,
                    Permission::StudentStatusChangesApprove,
                    Permission::AdvisorsManage,
                    Permission::ReferenceDataImport,
                    Permission::ApplicationsView,
                    Permission::ApplicationsMatriculate,
                    Permission::SectionsView,
                    Permission::AdditionalProgramsApprove,
                    Permission::EnrollmentsView,
                    Permission::RegistrationManage,
                    Permission::RegistrationHoldsManage,
                    Permission::RegistrationRulesManage,
                    Permission::GradesView,
                    Permission::GradesManage,
                    Permission::GradeChangesApprove,
                ],
            ],
            'academic_affairs' => [
                'display_name' => ['en' => 'Academic Affairs', 'ko' => '교무 담당', 'zh_CN' => '教务管理员'],
                'permissions' => [
                    ...$staff,
                    Permission::ReferenceDataImport,
                    Permission::LookupValuesManage,
                    Permission::OrgUnitsManage,
                    Permission::ProgramsManage,
                    Permission::FacilitiesManage,
                    Permission::TermsManage,
                    Permission::TermWindowsManage,
                    Permission::CourseCategoriesManage,
                    Permission::GradeScalesManage,
                    Permission::InstructorsManage,
                    Permission::StudentRecordsView,
                    Permission::CoursesManage,
                    Permission::CurriculumManage,
                    Permission::SectionsView,
                    Permission::SectionsManage,
                    Permission::EnrollmentsView,
                    Permission::RegistrationRulesManage,
                    Permission::GradesView,
                    Permission::GradeChangesRequest,
                    Permission::GradingPoliciesManage,
                    Permission::EvaluationsView,
                    Permission::EvaluationsManage,
                ],
            ],
            'certificate_officer' => [
                'display_name' => ['en' => 'Certificate Officer', 'ko' => '증명서 담당', 'zh_CN' => '证明书管理员'],
                'permissions' => [...$staff, Permission::StudentRecordsView, Permission::GradesView],
            ],
            // Assign with an org unit scope: the assistant sees that unit's students.
            'department_assistant' => [
                'display_name' => ['en' => 'Department Assistant', 'ko' => '학과 조교', 'zh_CN' => '院系助理'],
                'permissions' => [
                    ...$staff,
                    Permission::StudentRecordsView,
                    Permission::AdvisorsManage,
                    Permission::ApplicationsView,
                    Permission::SectionsView,
                    Permission::SectionsManage,
                    Permission::AdditionalProgramsApprove,
                    Permission::EnrollmentsView,
                    Permission::RegistrationManage,
                    Permission::GradesView,
                    Permission::EvaluationsView,
                ],
            ],
            'admissions_officer' => [
                'display_name' => ['en' => 'Admissions Officer', 'ko' => '입학 담당', 'zh_CN' => '招生管理员'],
                'permissions' => [
                    ...$staff,
                    Permission::AdmissionCyclesManage,
                    Permission::ApplicationsView,
                    Permission::ApplicationsReview,
                    Permission::ApplicationsMatriculate,
                ],
            ],
        ];

        $personas = [
            Persona::Applicant->value => ['en' => 'Applicant', 'ko' => '지원자', 'zh_CN' => '申请人'],
            Persona::Student->value => ['en' => 'Student', 'ko' => '학생', 'zh_CN' => '学生'],
            Persona::Alumni->value => ['en' => 'Alumni', 'ko' => '졸업생', 'zh_CN' => '校友'],
            Persona::Instructor->value => ['en' => 'Instructor', 'ko' => '교원', 'zh_CN' => '教师'],
            Persona::Advisor->value => ['en' => 'Advisor', 'ko' => '지도교수', 'zh_CN' => '指导教师'],
        ];

        $syncPermissionCatalog->handle();

        foreach ($templates as $name => $template) {
            $role = Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $template['display_name'], 'is_system' => $template['is_system'] ?? false],
            );

            if ($role->wasRecentlyCreated || $role->is_system) {
                $role->syncPermissions(array_map(fn (Permission $permission) => $permission->value, $template['permissions']));
            }
        }

        foreach ($personas as $persona => $displayName) {
            $role = Role::query()->firstOrCreate(
                ['name' => $persona, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'persona' => $persona],
            );

            // The persona link is what grants the role; keep it even if someone cleared it.
            if ($role->persona?->value !== $persona) {
                $role->forceFill(['persona' => $persona])->save();
            }
        }
    }
}
