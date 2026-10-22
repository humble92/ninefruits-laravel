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
            'leave_of_absence' => [StudentStatus::OnLeave, [$enrolled], true, 10, ['en' => 'Leave of absence', 'ko' => '휴학', 'zh_CN' => '休学']],
            'return_from_leave' => [StudentStatus::Enrolled, [$onLeave], true, 20, ['en' => 'Return from leave', 'ko' => '복학', 'zh_CN' => '复学']],
            'withdrawal' => [StudentStatus::Withdrawn, [$enrolled, $onLeave], true, 30, ['en' => 'Withdrawal', 'ko' => '자퇴', 'zh_CN' => '退学']],
            'dismissal' => [StudentStatus::Dismissed, [$enrolled, $onLeave], false, 40, ['en' => 'Dismissal', 'ko' => '제적', 'zh_CN' => '开除学籍']],
            'readmission' => [StudentStatus::Enrolled, [StudentStatus::Withdrawn->value, StudentStatus::Dismissed->value], false, 50, ['en' => 'Readmission', 'ko' => '재입학', 'zh_CN' => '重新入学']],
            'graduation' => [StudentStatus::Graduated, [$enrolled], false, 60, ['en' => 'Graduation', 'ko' => '졸업', 'zh_CN' => '毕业']],
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
