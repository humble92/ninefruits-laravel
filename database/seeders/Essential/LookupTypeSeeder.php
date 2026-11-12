<?php

namespace Database\Seeders\Essential;

use App\Models\Admission\Application;
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
        'ethnicity' => ['en' => 'Ethnicity', 'ko' => '민족', 'zh_CN' => '民族', 'lo' => 'ຊົນເຜົ່າ', 'de' => 'Ethnische Zugehörigkeit', 'fil' => 'Etnisidad', 'fr' => 'Ethnie', 'ja' => '民族', 'mn' => 'Үндэс угсаа', 'ms' => 'Bangsa', 'ne' => 'जातीयता', 'ru' => 'Национальность', 'th' => 'เชื้อชาติ', 'tr' => 'Etnik köken', 'uz' => 'Millati', 'vi' => 'Dân tộc'],
        Application::ADMISSION_TYPE_LOOKUP => ['en' => 'Admission type', 'ko' => '입학 구분', 'zh_CN' => '入学类别', 'lo' => 'ປະເພດການຮັບເຂົ້າຮຽນ', 'de' => 'Zulassungsart', 'fil' => 'Uri ng admisyon', 'fr' => 'Type d’admission', 'ja' => '入学区分', 'mn' => 'Элсэлтийн төрөл', 'ms' => 'Jenis kemasukan', 'ne' => 'भर्नाको प्रकार', 'ru' => 'Вид поступления', 'th' => 'ประเภทการรับเข้า', 'tr' => 'Giriş türü', 'uz' => 'Qabul turi', 'vi' => 'Diện tuyển sinh'],
        Application::DOCUMENT_TYPE_LOOKUP => ['en' => 'Application document type', 'ko' => '지원 제출 서류', 'zh_CN' => '申请材料类型', 'lo' => 'ປະເພດເອກະສານສະໝັກ', 'de' => 'Bewerbungsunterlage', 'fil' => 'Uri ng dokumento ng aplikasyon', 'fr' => 'Pièce justificative de candidature', 'ja' => '出願書類', 'mn' => 'Өргөдлийн баримт бичгийн төрөл', 'ms' => 'Jenis dokumen permohonan', 'ne' => 'आवेदन कागजातको प्रकार', 'ru' => 'Документ для заявления', 'th' => 'ประเภทเอกสารประกอบการสมัคร', 'tr' => 'Başvuru evrakı türü', 'uz' => 'Ariza hujjati turi', 'vi' => 'Loại tài liệu hồ sơ xét tuyển'],
        Room::ROOM_TYPE_LOOKUP => ['en' => 'Room type', 'ko' => '강의실 용도', 'zh_CN' => '教室用途', 'lo' => 'ການນຳໃຊ້ຫ້ອງຮຽນ', 'de' => 'Raumart', 'fil' => 'Uri ng silid', 'fr' => 'Type de salle', 'ja' => '教室種別', 'mn' => 'Танхимын төрөл', 'ms' => 'Jenis bilik', 'ne' => 'कोठाको प्रकार', 'ru' => 'Тип аудитории', 'th' => 'ประเภทห้อง', 'tr' => 'Derslik türü', 'uz' => 'Auditoriya turi', 'vi' => 'Loại phòng'],
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
