<?php

namespace Database\Seeders\Essential;

use App\Domain\Person\Enums\StudentStatus;
use App\Models\Person\StudentStatusChangeType;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Illuminate\Database\Seeder;

/**
 * System status change kinds (legacy SRALTERDEF). The transition (from statuses → status)
 * of system kinds is fixed; names and whether students may request them are editable.
 */
class StatusChangeTypeSeeder extends Seeder
{
    use SeedsTranslatedRows;

    public function run(): void
    {
        $enrolled = StudentStatus::Enrolled->value;
        $onLeave = StudentStatus::OnLeave->value;

        // code => [to, from, students may request, order, name]
        $types = [
            'leave_of_absence' => [StudentStatus::OnLeave, [$enrolled], true, 10, ['en' => 'Leave of absence', 'ko' => '휴학', 'zh_CN' => '休学', 'lo' => 'ການພັກການຮຽນ', 'de' => 'Beurlaubung', 'fil' => 'Leave of absence', 'fr' => 'Congé', 'ja' => '休学', 'mn' => 'Чөлөө', 'ms' => 'Penangguhan pengajian', 'ne' => 'अध्ययन बिदा', 'ru' => 'Академический отпуск', 'th' => 'ลาพักการศึกษา', 'tr' => 'Kayıt dondurma', 'uz' => 'Akademik taʼtil', 'vi' => 'Bảo lưu']],
            'return_from_leave' => [StudentStatus::Enrolled, [$onLeave], true, 20, ['en' => 'Return from leave', 'ko' => '복학', 'zh_CN' => '复学', 'lo' => 'ການກັບຄືນມາຮຽນ', 'de' => 'Rückkehr aus der Beurlaubung', 'fil' => 'Pagbalik mula sa leave', 'fr' => 'Reprise d’études', 'ja' => '復学', 'mn' => 'Чөлөөнөөс эргэн орох', 'ms' => 'Sambung semula pengajian', 'ne' => 'अध्ययनमा पुनरागमन', 'ru' => 'Выход из академического отпуска', 'th' => 'กลับเข้าศึกษา', 'tr' => 'Kayıt dondurma sonrası dönüş', 'uz' => 'Akademik taʼtildan qaytish', 'vi' => 'Trở lại học']],
            'withdrawal' => [StudentStatus::Withdrawn, [$enrolled, $onLeave], true, 30, ['en' => 'Withdrawal', 'ko' => '자퇴', 'zh_CN' => '退学', 'lo' => 'ການອອກຈາກການຮຽນ', 'de' => 'Exmatrikulation auf Antrag', 'fil' => 'Pag-withdraw', 'fr' => 'Abandon', 'ja' => '退学', 'mn' => 'Сургуулиас гарах', 'ms' => 'Tarik diri', 'ne' => 'अध्ययन परित्याग', 'ru' => 'Отчисление по собственному желанию', 'th' => 'ลาออก', 'tr' => 'Kendi isteğiyle ayrılma', 'uz' => 'Oʻqishdan chiqish', 'vi' => 'Thôi học']],
            'dismissal' => [StudentStatus::Dismissed, [$enrolled, $onLeave], false, 40, ['en' => 'Dismissal', 'ko' => '제적', 'zh_CN' => '开除学籍', 'lo' => 'ການໄລ່ອອກຈາກການຮຽນ', 'de' => 'Exmatrikulation von Amts wegen', 'fil' => 'Pagkatanggal', 'fr' => 'Exclusion', 'ja' => '除籍', 'mn' => 'Сургуулиас хасах', 'ms' => 'Pemberhentian', 'ne' => 'निष्कासन', 'ru' => 'Отчисление', 'th' => 'พ้นสภาพ', 'tr' => 'İlişik kesme', 'uz' => 'Oʻqishdan chetlashtirish', 'vi' => 'Buộc thôi học']],
            'readmission' => [StudentStatus::Enrolled, [StudentStatus::Withdrawn->value, StudentStatus::Dismissed->value], false, 50, ['en' => 'Readmission', 'ko' => '재입학', 'zh_CN' => '重新入学', 'lo' => 'ການຮັບເຂົ້າຮຽນຄືນ', 'de' => 'Wiedereinschreibung', 'fil' => 'Muling pagtanggap', 'fr' => 'Réadmission', 'ja' => '再入学', 'mn' => 'Дахин элсэх', 'ms' => 'Kemasukan semula', 'ne' => 'पुनर्भर्ना', 'ru' => 'Восстановление', 'th' => 'คืนสภาพนักศึกษา', 'tr' => 'Yeniden kayıt', 'uz' => 'Oʻqishga qayta tiklash', 'vi' => 'Tiếp nhận học lại']],
            'graduation' => [StudentStatus::Graduated, [$enrolled], false, 60, ['en' => 'Graduation', 'ko' => '졸업', 'zh_CN' => '毕业', 'lo' => 'ການຈົບການສຶກສາ', 'de' => 'Studienabschluss', 'fil' => 'Pagtatapos', 'fr' => 'Obtention du diplôme', 'ja' => '卒業', 'mn' => 'Төгсөлт', 'ms' => 'Bergraduat', 'ne' => 'स्नातक', 'ru' => 'Выпуск', 'th' => 'สำเร็จการศึกษา', 'tr' => 'Mezuniyet', 'uz' => 'Bitirish', 'vi' => 'Tốt nghiệp']],
        ];

        foreach ($types as $code => [$to, $from, $canRequest, $order, $name]) {
            $type = $this->seedRow(StudentStatusChangeType::class, ['code' => $code], ['name' => $name], [
                'to_status' => $to,
                'from_statuses' => $from,
                'can_student_request' => $canRequest,
                'sort_order' => $order,
            ]);

            $type->forceFill(['is_system' => true, 'to_status' => $to, 'from_statuses' => $from])->save();
        }
    }
}
