<?php

namespace Database\Seeders\Essential;

use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\Persona;
use App\Models\Security\Role;
use Illuminate\Database\Seeder;

/**
 * Default roles based on the legacy Oracle roles (porting/porting-plan.md section 7.2).
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
                'display_name' => ['en' => 'System Administrator', 'ko' => '시스템 관리자', 'zh_CN' => '系统管理员', 'lo' => 'ຜູ້ບໍລິຫານລະບົບ', 'de' => 'Systemadministration', 'fil' => 'Administrator ng Sistema', 'fr' => 'Administrateur système', 'ja' => 'システム管理者', 'mn' => 'Системийн админ', 'ms' => 'Pentadbir Sistem', 'ne' => 'प्रणाली प्रशासक', 'ru' => 'Системный администратор', 'th' => 'ผู้ดูแลระบบ', 'tr' => 'Sistem Yöneticisi', 'uz' => 'Tizim administratori', 'vi' => 'Quản trị hệ thống'],
                'is_system' => true,
                'permissions' => Permission::cases(),
            ],
            'registrar' => [
                'display_name' => ['en' => 'Registrar', 'ko' => '학적 담당', 'zh_CN' => '学籍管理员', 'lo' => 'ຜູ້ຮັບຜິດຊອບທະບຽນນັກສຶກສາ', 'de' => 'Studierendensekretariat', 'fil' => 'Registrar', 'fr' => 'Scolarité', 'ja' => '学籍担当', 'mn' => 'Оюутны бүртгэл хариуцсан ажилтан', 'ms' => 'Pegawai Pendaftar', 'ne' => 'रजिस्ट्रार', 'ru' => 'Сотрудник учебного отдела', 'th' => 'เจ้าหน้าที่ทะเบียน', 'tr' => 'Öğrenci İşleri', 'uz' => 'Registrator', 'vi' => 'Cán bộ quản lý sinh viên'],
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
                    Permission::GradeExceptionsReview,
                    Permission::CertificatesIssue,
                    Permission::CertificatesManage,
                    Permission::AssessmentsRecord,
                    Permission::AssessmentsManage,
                    Permission::GraduationAudit,
                    Permission::GraduationProcess,
                    Permission::ReportsView,
                ],
            ],
            'academic_affairs' => [
                'display_name' => ['en' => 'Academic Affairs', 'ko' => '교무 담당', 'zh_CN' => '教务管理员', 'lo' => 'ຜູ້ຮັບຜິດຊອບວິຊາການ', 'de' => 'Lehrplanung', 'fil' => 'Academic Affairs', 'fr' => 'Affaires pédagogiques', 'ja' => '教務担当', 'mn' => 'Сургалтын албаны ажилтан', 'ms' => 'Pegawai Hal Ehwal Akademik', 'ne' => 'शैक्षिक प्रशासन', 'ru' => 'Сотрудник учебного управления', 'th' => 'เจ้าหน้าที่ฝ่ายวิชาการ', 'tr' => 'Akademik İşler', 'uz' => 'Oʻquv-uslubiy boshqarma', 'vi' => 'Cán bộ đào tạo'],
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
                    Permission::CertificatesManage,
                    Permission::AssessmentsRecord,
                    Permission::AssessmentsManage,
                    Permission::GraduationAudit,
                    Permission::ReportsView,
                    Permission::LmsManage,
                ],
            ],
            'certificate_officer' => [
                'display_name' => ['en' => 'Certificate Officer', 'ko' => '증명서 담당', 'zh_CN' => '证明书管理员', 'lo' => 'ຜູ້ຮັບຜິດຊອບໃບຢັ້ງຢືນ', 'de' => 'Bescheinigungsstelle', 'fil' => 'Tagapangasiwa ng Sertipiko', 'fr' => 'Responsable des attestations', 'ja' => '証明書担当', 'mn' => 'Тодорхойлолт хариуцсан ажилтан', 'ms' => 'Pegawai Sijil', 'ne' => 'प्रमाणपत्र अधिकृत', 'ru' => 'Ответственный за справки', 'th' => 'เจ้าหน้าที่ออกหนังสือรับรอง', 'tr' => 'Belge Sorumlusu', 'uz' => 'Maʼlumotnomalar masʼuli', 'vi' => 'Cán bộ cấp giấy chứng nhận'],
                'permissions' => [...$staff, Permission::StudentRecordsView, Permission::GradesView, Permission::CertificatesIssue],
            ],
            // Assign with an org unit scope: the assistant sees that unit's students.
            'department_assistant' => [
                'display_name' => ['en' => 'Department Assistant', 'ko' => '학과 조교', 'zh_CN' => '院系助理', 'lo' => 'ຜູ້ຊ່ວຍພາກວິຊາ', 'de' => 'Institutssekretariat', 'fil' => 'Katuwang ng Departamento', 'fr' => 'Gestionnaire de département', 'ja' => '学科助手', 'mn' => 'Тэнхимийн туслах ажилтан', 'ms' => 'Pembantu Jabatan', 'ne' => 'विभागीय सहायक', 'ru' => 'Методист кафедры', 'th' => 'เจ้าหน้าที่ภาควิชา', 'tr' => 'Bölüm Asistanı', 'uz' => 'Kafedra yordamchisi', 'vi' => 'Giáo vụ khoa'],
                'permissions' => [
                    ...$staff,
                    Permission::StudentRecordsView,
                    Permission::ReportsView,
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
                'display_name' => ['en' => 'Admissions Officer', 'ko' => '입학 담당', 'zh_CN' => '招生管理员', 'lo' => 'ຜູ້ຮັບຜິດຊອບການຮັບເຂົ້າຮຽນ', 'de' => 'Zulassungsstelle', 'fil' => 'Admissions Officer', 'fr' => 'Chargé des admissions', 'ja' => '入試担当', 'mn' => 'Элсэлт хариуцсан ажилтан', 'ms' => 'Pegawai Kemasukan', 'ne' => 'भर्ना अधिकृत', 'ru' => 'Сотрудник приёмной комиссии', 'th' => 'เจ้าหน้าที่รับสมัคร', 'tr' => 'Öğrenci Kabul Sorumlusu', 'uz' => 'Qabul komissiyasi xodimi', 'vi' => 'Cán bộ tuyển sinh'],
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
            Persona::Applicant->value => ['en' => 'Applicant', 'ko' => '지원자', 'zh_CN' => '申请人', 'lo' => 'ຜູ້ສະໝັກ', 'de' => 'Bewerber/in', 'fil' => 'Aplikante', 'fr' => 'Candidat', 'ja' => '志願者', 'mn' => 'Өргөдөл гаргагч', 'ms' => 'Pemohon', 'ne' => 'आवेदक', 'ru' => 'Абитуриент', 'th' => 'ผู้สมัคร', 'tr' => 'Aday', 'uz' => 'Abituriyent', 'vi' => 'Thí sinh'],
            Persona::Student->value => ['en' => 'Student', 'ko' => '학생', 'zh_CN' => '学生', 'lo' => 'ນັກສຶກສາ', 'de' => 'Studierende/r', 'fil' => 'Mag-aaral', 'fr' => 'Étudiant', 'ja' => '学生', 'mn' => 'Оюутан', 'ms' => 'Pelajar', 'ne' => 'विद्यार्थी', 'ru' => 'Студент', 'th' => 'นักศึกษา', 'tr' => 'Öğrenci', 'uz' => 'Talaba', 'vi' => 'Sinh viên'],
            Persona::Alumni->value => ['en' => 'Alumni', 'ko' => '졸업생', 'zh_CN' => '校友', 'lo' => 'ສິດເກົ່າ', 'de' => 'Alumni', 'fil' => 'Alumni', 'fr' => 'Ancien étudiant', 'ja' => '卒業生', 'mn' => 'Төгсөгч', 'ms' => 'Alumni', 'ne' => 'पूर्वविद्यार्थी', 'ru' => 'Выпускник', 'th' => 'ศิษย์เก่า', 'tr' => 'Mezun', 'uz' => 'Bitiruvchi', 'vi' => 'Cựu sinh viên'],
            Persona::Instructor->value => ['en' => 'Instructor', 'ko' => '교원', 'zh_CN' => '教师', 'lo' => 'ອາຈານ', 'de' => 'Lehrperson', 'fil' => 'Instruktor', 'fr' => 'Enseignant', 'ja' => '教員', 'mn' => 'Багш', 'ms' => 'Pensyarah', 'ne' => 'प्राध्यापक', 'ru' => 'Преподаватель', 'th' => 'อาจารย์', 'tr' => 'Öğretim Elemanı', 'uz' => 'Oʻqituvchi', 'vi' => 'Giảng viên'],
            Persona::Advisor->value => ['en' => 'Advisor', 'ko' => '지도교수', 'zh_CN' => '指导教师', 'lo' => 'ອາຈານທີ່ປຶກສາ', 'de' => 'Mentor/in', 'fil' => 'Tagapayo', 'fr' => 'Enseignant référent', 'ja' => '指導教員', 'mn' => 'Зөвлөх багш', 'ms' => 'Penasihat akademik', 'ne' => 'सल्लाहकार प्राध्यापक', 'ru' => 'Куратор', 'th' => 'อาจารย์ที่ปรึกษา', 'tr' => 'Danışman', 'uz' => 'Akademik maslahatchi', 'vi' => 'Cố vấn học tập'],
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
