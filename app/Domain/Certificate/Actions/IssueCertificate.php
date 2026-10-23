<?php

namespace App\Domain\Certificate\Actions;

use App\Domain\Certificate\Enums\CertificateAudience;
use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Domain\Certificate\Services\CertificateRenderer;
use App\Domain\Certificate\Services\CertificateSerialGenerator;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Certificate\CertificateIssue;
use App\Models\Certificate\CertificateType;
use App\Models\Person\StudentRecord;
use App\Models\Registration\RegistrationHold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Issues a certificate (staff only, docs/porting-plan.md 17.5): a ledger row with a serial
 * number and verification code, the PDF rendered and stored, the checksum kept. Holds that
 * block certificates refuse. Revocation keeps the row and the file.
 */
class IssueCertificate
{
    public function __construct(
        private readonly CertificateSerialGenerator $serials,
        private readonly CertificateRenderer $renderer,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(StudentRecord $record, CertificateType $type, string $locale, User $staff, ?string $purpose = null, int $copies = 1): CertificateIssue
    {
        if (! $staff->can(Permission::CertificatesIssue->value) || ! $record->isVisibleTo($staff)) {
            throw new AuthorizationException;
        }

        if (! $type->is_active) {
            throw ValidationException::withMessages(['certificate_type_id' => __('certificate.errors.type_inactive')]);
        }

        $this->checkAudience($record, $type);

        if (RegistrationHold::query()->where('student_record_id', $record->id)->where('blocks_certificates', true)->inForce()->exists()) {
            throw ValidationException::withMessages(['student_record_id' => __('certificate.errors.hold')]);
        }

        $issue = DB::transaction(function () use ($record, $type, $locale, $staff, $purpose, $copies) {
            $now = CarbonImmutable::now();

            return CertificateIssue::query()->create([
                'student_record_id' => $record->id,
                'certificate_type_id' => $type->id,
                'locale' => $locale,
                'serial_number' => $this->serials->next($type, $now),
                'verification_code' => Str::upper(Str::random(20)),
                'purpose' => $purpose !== null && trim($purpose) !== '' ? trim($purpose) : null,
                'copies' => max(1, min(20, $copies)),
                'issued_at' => $now,
                'issued_by_id' => $staff->id,
            ]);
        });

        $this->storePdf($issue);
        $this->audit->record('certificate.issued', $issue, $purpose, [
            'student_number' => $record->student_number, 'type' => $type->code, 'locale' => $locale, 'serial' => $issue->serial_number, 'copies' => $issue->copies,
        ], $staff);

        return $issue;
    }

    /**
     * Renders the PDF again from the current data and replaces the stored file (e.g. after a
     * template fix); the serial number and code stay.
     */
    public function rerender(CertificateIssue $issue, ?User $staff): CertificateIssue
    {
        if ($staff !== null && ! $staff->can(Permission::CertificatesIssue->value)) {
            throw new AuthorizationException;
        }

        $this->storePdf($issue);
        $this->audit->record('certificate.rerendered', $issue, null, ['serial' => $issue->serial_number], $staff);

        return $issue;
    }

    public function revoke(CertificateIssue $issue, User $staff, string $reason): CertificateIssue
    {
        if (! $staff->can(Permission::CertificatesIssue->value)) {
            throw new AuthorizationException;
        }

        if ($issue->status === CertificateIssueStatus::Revoked) {
            throw ValidationException::withMessages(['status' => __('certificate.errors.already_revoked')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $issue->forceFill(['status' => CertificateIssueStatus::Revoked, 'revoked_at' => CarbonImmutable::now(), 'revoked_by_id' => $staff->id, 'revocation_reason' => $reason])->save();
        $this->audit->record('certificate.revoked', $issue, $reason, ['serial' => $issue->serial_number], $staff);

        return $issue;
    }

    private function checkAudience(StudentRecord $record, CertificateType $type): void
    {
        $graduated = $record->status === StudentStatus::Graduated;
        $allowed = match ($type->audience) {
            CertificateAudience::Any => true,
            CertificateAudience::Enrolled => ! $graduated,
            CertificateAudience::Graduated => $graduated,
        };

        if (! $allowed) {
            throw ValidationException::withMessages(['certificate_type_id' => __('certificate.errors.audience', ['audience' => $type->audience->label()])]);
        }
    }

    private function storePdf(CertificateIssue $issue): void
    {
        $pdf = $this->renderer->render($issue);
        $issue->clearMediaCollection(CertificateIssue::PDF_COLLECTION);
        $issue->addMediaFromString($pdf)->usingFileName($issue->serial_number.'.pdf')->toMediaCollection(CertificateIssue::PDF_COLLECTION);
        $issue->forceFill(['pdf_checksum' => hash('sha256', $pdf)])->save();
    }
}
