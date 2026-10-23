<?php

namespace App\Domain\Certificate\Services;

use App\Domain\Certificate\Data\CertificateContent;
use App\Domain\Certificate\Settings\CertificateSettings;
use App\Domain\Grading\Services\GradeReport;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Models\Certificate\CertificateIssue;
use App\Models\Certificate\CertificateTemplate;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Support\Pdf\PdfRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Builds a certificate from the type's template in the issue's language: the facts table,
 * the body with {placeholders} filled in, the grades for transcripts, the serial number,
 * and a QR code to the public verification page. Chromium renders it to PDF.
 */
class CertificateRenderer
{
    /** Placeholders a template may use. */
    public const array PLACEHOLDERS = [
        'institution', 'student_name', 'student_number', 'program', 'org_unit', 'admission_date', 'status', 'year_level',
        'graduation_date', 'degree_number', 'birth_date', 'gender', 'nationality', 'issue_date', 'serial_number', 'purpose', 'copies',
    ];

    public function __construct(
        private readonly PdfRenderer $pdf,
        private readonly GradeReport $grades,
        private readonly InstitutionSettings $institution,
        private readonly CertificateSettings $settings,
    ) {}

    public function render(CertificateIssue $issue): string
    {
        return $this->pdf->renderView('pdf.certificate', ['content' => $this->content($issue)]);
    }

    public function content(CertificateIssue $issue): CertificateContent
    {
        $issue->loadMissing(['type', 'studentRecord.person.nationality', 'studentRecord.program', 'studentRecord.orgUnit']);
        $record = $issue->studentRecord;
        $template = $this->template($issue);
        $locale = $issue->locale;
        $values = $this->values($issue, $record, $locale);
        $fill = fn (?string $text) => $text === null ? null : preg_replace_callback('/\{([a-z_]+)\}/', fn (array $m) => $values[$m[1]] ?? $m[0], $text);

        $terms = $issue->type->has_grades ? $this->grades->terms($record) : null;
        $latest = $terms?->last()['record'] ?? null;

        return new CertificateContent(
            institution: $values['institution'],
            title: $fill($template->title),
            facts: $this->facts($issue, $values, $locale),
            paragraphs: array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $fill($template->body)) ?: []))),
            footer: $fill($template->footer),
            signer: $template->signer ?? ($this->settings->signer[$locale] ?? null),
            serialNumber: $issue->serial_number,
            issuedOn: $values['issue_date'],
            verificationUrl: route('certificates.verify', $issue->verification_code),
            qrSvg: $this->qr(route('certificates.verify', $issue->verification_code)),
            photoDataUri: $issue->type->has_photo ? $this->photo($record->person) : null,
            terms: $terms?->values()->all(),
            cumulativeGpa: $latest?->cumulative_gpa,
            earnedCredits: $latest === null ? null : (string) (float) $latest->cumulative_earned_credits,
        );
    }

    public function template(CertificateIssue $issue): CertificateTemplate
    {
        return CertificateTemplate::query()->where('certificate_type_id', $issue->certificate_type_id)->where('locale', $issue->locale)->first()
            ?? throw ValidationException::withMessages(['locale' => __('certificate.errors.no_template', ['locale' => $issue->locale])]);
    }

    /**
     * @return array<string, string>
     */
    private function values(CertificateIssue $issue, StudentRecord $record, string $locale): array
    {
        $date = fn (?CarbonImmutable $date) => $date === null ? '' : $date->locale($locale)->isoFormat('LL');
        $timezone = $this->institution->timezone;

        return [
            'institution' => $this->institution->name[$locale] ?? reset($this->institution->name) ?: (string) config('app.name'),
            'student_name' => $record->person->displayName($locale),
            'student_number' => $record->student_number,
            'program' => $record->program->getTranslation('name', $locale),
            'org_unit' => $record->orgUnit->getTranslation('name', $locale),
            'admission_date' => $date($record->admission_date),
            'status' => $record->status->label(),
            'year_level' => (string) $record->year_level,
            'graduation_date' => $date($record->graduation_date),
            'degree_number' => (string) ($record->degree_number ?? ''),
            'birth_date' => $date($record->person->birth_date),
            'gender' => $record->person->gender?->label() ?? '',
            'nationality' => $record->person->nationality?->getTranslation('name', $locale) ?? '',
            'issue_date' => $date($issue->issued_at->timezone($timezone)),
            'serial_number' => $issue->serial_number,
            'purpose' => (string) ($issue->purpose ?? ''),
            'copies' => (string) $issue->copies,
        ];
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function facts(CertificateIssue $issue, array $values, string $locale): array
    {
        $label = fn (string $key) => __('certificate.facts.'.$key, [], $locale);
        $facts = [
            $label('student_name') => $values['student_name'],
            $label('student_number') => $values['student_number'],
            $label('birth_date') => $values['birth_date'],
            $label('gender') => $values['gender'],
            $label('nationality') => $values['nationality'],
            $label('program') => $values['program'],
            $label('admission_date') => $values['admission_date'],
        ];

        if ($issue->type->audience->value === 'graduated' || $issue->studentRecord->graduation_date !== null) {
            $facts[$label('graduation_date')] = $values['graduation_date'];
        } else {
            $facts[$label('status')] = $values['status'];
            $facts[$label('year_level')] = $values['year_level'];
        }

        return array_filter($facts, fn (string $value) => $value !== '');
    }

    private function qr(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(140, 0), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    private function photo(Person $person): ?string
    {
        $media = $person->getFirstMedia(Person::PHOTO_COLLECTION);

        if ($media === null) {
            return null;
        }

        $stream = $media->stream();
        $bytes = stream_get_contents($stream);
        fclose($stream);

        return $bytes === false ? null : 'data:'.$media->mime_type.';base64,'.base64_encode($bytes);
    }
}
