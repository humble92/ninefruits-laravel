<?php

namespace App\Console\Commands\Certificate;

use App\Console\Commands\AuditedCommand;
use App\Domain\Certificate\Actions\IssueCertificate;
use App\Models\Certificate\CertificateIssue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('certificate:render {issue : Serial number of the issue} {--reason= : Why (required)}')]
#[Description('Render an issued certificate\'s PDF again from the current data and template, keeping its number and code')]
class RenderCertificate extends AuditedCommand
{
    public function handle(IssueCertificate $certificates): int
    {
        if (blank($this->option('reason'))) {
            $this->components->error('--reason is required.');

            return self::FAILURE;
        }

        $issue = CertificateIssue::query()->where('serial_number', (string) $this->argument('issue'))->first();

        if ($issue === null) {
            $this->components->error('Unknown serial number.');

            return self::FAILURE;
        }

        $certificates->rerender($issue, $this->actor());
        $this->recordResult(['serial' => $issue->serial_number, 'checksum' => $issue->pdf_checksum]);
        $this->components->info("Rendered {$issue->serial_number} (sha256 {$issue->pdf_checksum}).");

        return self::SUCCESS;
    }
}
