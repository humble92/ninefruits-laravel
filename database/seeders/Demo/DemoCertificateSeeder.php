<?php

namespace Database\Seeders\Demo;

use App\Domain\Certificate\Actions\IssueCertificate;
use App\Domain\Person\Enums\StudentStatus;
use App\Models\Certificate\CertificateIssue;
use App\Models\Certificate\CertificateType;
use App\Models\Person\StudentRecord;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Illuminate\Database\Seeder;

/**
 * A few issued certificates: enrollment certificates for current students, graduation
 * certificates for alumni, one transcript, and one revoked issue. Rendering needs the PDF
 * service, so the count stays small.
 */
class DemoCertificateSeeder extends Seeder
{
    private const array PURPOSES = ['Scholarship application', '장학금 신청', 'Visa extension', '취업 제출용', '奖学金申请'];

    public function run(DemoOptions $options, IssueCertificate $issue): void
    {
        $officer = User::query()->where('email', 'certificate-officer@'.config('setup.demo.email_domain'))->first();
        $types = CertificateType::query()->where('is_active', true)->get()->keyBy('code');

        if ($officer === null || $types->isEmpty() || CertificateIssue::query()->exists()) {
            return;
        }

        $enrolled = StudentRecord::query()->where('status', StudentStatus::Enrolled)->inRandomOrder()->limit($options->scale(4))->get();
        $graduated = StudentRecord::query()->where('status', StudentStatus::Graduated)->inRandomOrder()->limit($options->scale(2))->get();
        $issued = [];

        foreach ($enrolled as $index => $record) {
            if (isset($types['ENR'])) {
                $issued[] = $issue->handle($record, $types['ENR'], ['ko', 'en', 'zh_CN'][$index % 3], $officer, self::PURPOSES[$index % count(self::PURPOSES)], 1 + $index % 2);
            }
        }

        foreach ($graduated as $index => $record) {
            if (isset($types['GRD'])) {
                $issued[] = $issue->handle($record, $types['GRD'], ['en', 'ko'][$index % 2], $officer, 'Employment');
            }
        }

        if (isset($types['TRN']) && ($record = $graduated->first() ?? $enrolled->first()) !== null) {
            $issued[] = $issue->handle($record, $types['TRN'], 'en', $officer, 'Graduate school application');
        }

        if (count($issued) > 2) {
            $issue->revoke($issued[1], $officer, 'Issued with a wrong purpose; re-issued.');
        }
    }
}
