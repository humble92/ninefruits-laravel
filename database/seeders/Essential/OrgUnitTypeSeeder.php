<?php

namespace Database\Seeders\Essential;

use App\Models\Organization\OrgUnitType;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Illuminate\Database\Seeder;

/**
 * Default org unit types. Institutions can rename them or add more.
 */
class OrgUnitTypeSeeder extends Seeder
{
    use SeedsTranslatedRows;

    public function run(): void
    {
        $types = [
            'university' => [10, true, ['en' => 'University', 'ko' => '대학교', 'zh_CN' => '大学', 'lo' => 'ມະຫາວິທະຍາໄລ', 'de' => 'Universität', 'fil' => 'Unibersidad', 'fr' => 'Université', 'ja' => '大学', 'mn' => 'Их сургууль', 'ms' => 'Universiti', 'ne' => 'विश्वविद्यालय', 'ru' => 'Университет', 'th' => 'มหาวิทยาลัย', 'tr' => 'Üniversite', 'uz' => 'Universitet', 'vi' => 'Trường đại học']],
            'college' => [20, true, ['en' => 'College', 'ko' => '단과대학', 'zh_CN' => '学院', 'lo' => 'ຄະນະ', 'de' => 'Fakultät', 'fil' => 'Kolehiyo', 'fr' => 'Faculté', 'ja' => '学部', 'mn' => 'Бүрэлдэхүүн сургууль', 'ms' => 'Fakulti', 'ne' => 'सङ्काय', 'ru' => 'Институт', 'th' => 'คณะ', 'tr' => 'Fakülte', 'uz' => 'Fakultet', 'vi' => 'Trường thành viên']],
            'department' => [30, true, ['en' => 'Department', 'ko' => '학과', 'zh_CN' => '系', 'lo' => 'ພາກວິຊາ', 'de' => 'Institut', 'fil' => 'Departamento', 'fr' => 'Département', 'ja' => '学科', 'mn' => 'Тэнхим', 'ms' => 'Jabatan', 'ne' => 'विभाग', 'ru' => 'Кафедра', 'th' => 'ภาควิชา', 'tr' => 'Bölüm', 'uz' => 'Kafedra', 'vi' => 'Khoa']],
            'major' => [40, true, ['en' => 'Major', 'ko' => '전공', 'zh_CN' => '专业', 'lo' => 'ສາຂາວິຊາ', 'de' => 'Studienfach', 'fil' => 'Major', 'fr' => 'Spécialité', 'ja' => '専攻', 'mn' => 'Мэргэжил', 'ms' => 'Major', 'ne' => 'मुख्य विषय', 'ru' => 'Специальность', 'th' => 'สาขาวิชา', 'tr' => 'Ana dal', 'uz' => 'Mutaxassislik', 'vi' => 'Ngành']],
            'office' => [90, false, ['en' => 'Administrative office', 'ko' => '행정 부서', 'zh_CN' => '行政部门', 'lo' => 'ຫ້ອງການບໍລິຫານ', 'de' => 'Verwaltungsstelle', 'fil' => 'Opisinang administratibo', 'fr' => 'Service administratif', 'ja' => '事務部署', 'mn' => 'Захиргааны алба', 'ms' => 'Pejabat pentadbiran', 'ne' => 'प्रशासनिक कार्यालय', 'ru' => 'Административное подразделение', 'th' => 'หน่วยงานสนับสนุน', 'tr' => 'İdari birim', 'uz' => 'Maʼmuriy boʻlim', 'vi' => 'Phòng ban hành chính']],
        ];

        foreach ($types as $code => [$sortOrder, $isAcademic, $name]) {
            $this->seedRow(OrgUnitType::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder, 'is_academic' => $isAcademic]);
        }
    }
}
