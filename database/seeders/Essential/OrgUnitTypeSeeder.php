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
            'university' => [10, true, ['en' => 'University', 'ko' => '대학교', 'zh_CN' => '大学', 'lo' => 'ມະຫາວິທະຍາໄລ']],
            'college' => [20, true, ['en' => 'College', 'ko' => '단과대학', 'zh_CN' => '学院', 'lo' => 'ຄະນະ']],
            'department' => [30, true, ['en' => 'Department', 'ko' => '학과', 'zh_CN' => '系', 'lo' => 'ພາກວິຊາ']],
            'major' => [40, true, ['en' => 'Major', 'ko' => '전공', 'zh_CN' => '专业', 'lo' => 'ສາຂາວິຊາ']],
            'office' => [90, false, ['en' => 'Administrative office', 'ko' => '행정 부서', 'zh_CN' => '行政部门', 'lo' => 'ຫ້ອງການບໍລິຫານ']],
        ];

        foreach ($types as $code => [$sortOrder, $isAcademic, $name]) {
            $this->seedRow(OrgUnitType::class, ['code' => $code], ['name' => $name], ['sort_order' => $sortOrder, 'is_academic' => $isAcademic]);
        }
    }
}
