<?php

namespace App\Console\Commands\Certificate;

use App\Domain\Certificate\Services\CertificateVerification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('certificate:verify {code : Verification code printed on the certificate}')]
#[Description('Check a certificate verification code, as the public page does')]
class VerifyCertificate extends Command
{
    public function handle(CertificateVerification $verification): int
    {
        $result = $verification->check((string) $this->argument('code'));

        if ($result['issue'] !== null) {
            $issue = $result['issue'];
            $this->components->twoColumnDetail('Type', $issue->type->code.' ('.$issue->locale.')');
            $this->components->twoColumnDetail('Serial', $issue->serial_number);
            $this->components->twoColumnDetail('Issued', $issue->issued_at->toDateString());
            $this->components->twoColumnDetail('Holder', $result['holder'].' '.$result['student_number']);
        }

        if ($result['valid']) {
            $this->components->info('Valid.');

            return self::SUCCESS;
        }

        $this->components->warn('Not valid: '.$result['reason'].'.');

        return 2;
    }
}
