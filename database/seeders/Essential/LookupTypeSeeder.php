<?php

namespace Database\Seeders\Essential;

use App\Models\Facility\Room;
use App\Models\Institution\LookupType;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Illuminate\Database\Seeder;

/**
 * Code lists the application refers to by code. Values are institution specific and come
 * from profiles, CSV import or the admin panel.
 */
class LookupTypeSeeder extends Seeder
{
    use SeedsTranslatedRows;

    /** @var array<string, array<string, string>> */
    public const array SYSTEM_TYPES = [
        'ethnicity' => ['en' => 'Ethnicity', 'ko' => '민족', 'zh_CN' => '民族'],
        'admission_type' => ['en' => 'Admission type', 'ko' => '입학 구분', 'zh_CN' => '入学类别'],
        Room::ROOM_TYPE_LOOKUP => ['en' => 'Room type', 'ko' => '강의실 용도', 'zh_CN' => '教室用途'],
    ];

    public function run(): void
    {
        foreach (self::SYSTEM_TYPES as $code => $name) {
            $type = $this->seedRow(LookupType::class, ['code' => $code], ['name' => $name]);

            if (! $type->is_system) {
                $type->forceFill(['is_system' => true])->save();
            }
        }
    }
}
