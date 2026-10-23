<?php

namespace App\Domain\Certificate\Services;

use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Domain\Certificate\Settings\CertificateSettings;
use App\Models\Certificate\CertificateIssue;
use App\Support\Database\DatabaseSessionContext;

/**
 * Checks a verification code for anyone (the public page, certificate:verify). Looks the
 * issue up in the system context (no user is signed in) and tells only what the paper
 * already shows: kind, serial number, issue date, the holder's masked name and number.
 */
class CertificateVerification
{
    public function __construct(
        private readonly CertificateSettings $settings,
        private readonly DatabaseSessionContext $context,
    ) {}

    /**
     * @return array{valid: bool, reason: string|null, issue: CertificateIssue|null, holder: string|null, student_number: string|null}
     */
    public function check(string $code): array
    {
        $code = strtoupper(trim($code));

        if ($code === '' || strlen($code) > 40) {
            return $this->result(false, 'unknown');
        }

        /** @var CertificateIssue|null $issue */
        $issue = $this->context->asSystem('certificate:verify', fn () => CertificateIssue::query()->where('verification_code', $code)
            ->with(['type', 'studentRecord.person'])->first());

        if ($issue === null) {
            return $this->result(false, 'unknown');
        }

        if ($issue->status === CertificateIssueStatus::Revoked) {
            return $this->result(false, 'revoked', $issue);
        }

        $days = $this->settings->verification_valid_days;

        if ($days > 0 && $issue->issued_at->addDays($days)->isPast()) {
            return $this->result(false, 'expired', $issue);
        }

        return $this->result(true, null, $issue);
    }

    /**
     * @return array{valid: bool, reason: string|null, issue: CertificateIssue|null, holder: string|null, student_number: string|null}
     */
    private function result(bool $valid, ?string $reason, ?CertificateIssue $issue = null): array
    {
        return [
            'valid' => $valid,
            'reason' => $reason,
            'issue' => $issue,
            'holder' => $issue === null ? null : self::maskName($issue->studentRecord->person->displayName($issue->locale)),
            'student_number' => $issue === null ? null : self::maskNumber($issue->studentRecord->student_number),
        ];
    }

    public static function maskName(string $name): string
    {
        $chars = mb_str_split($name);

        if (count($chars) <= 2) {
            return ($chars[0] ?? '').str_repeat('*', max(0, count($chars) - 1));
        }

        return $chars[0].str_repeat('*', count($chars) - 2).$chars[count($chars) - 1];
    }

    public static function maskNumber(string $number): string
    {
        return strlen($number) <= 4 ? $number : substr($number, 0, 4).str_repeat('*', strlen($number) - 4);
    }
}
