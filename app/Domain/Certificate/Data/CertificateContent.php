<?php

namespace App\Domain\Certificate\Data;

use App\Models\Grading\TermRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use Illuminate\Support\Collection;

/**
 * A certificate's rendered wording and data for the PDF view.
 */
final readonly class CertificateContent
{
    /**
     * @param  list<string>  $paragraphs
     * @param  array<string, string>  $facts  label => value, printed as a table above the body
     * @param  list<array{term: Term, enrollments: Collection<int, Enrollment>, record: TermRecord|null}>|null  $terms  oldest first, only for kinds that include grades
     */
    public function __construct(
        public string $institution,
        public string $title,
        public array $facts,
        public array $paragraphs,
        public ?string $footer,
        public ?string $signer,
        public string $serialNumber,
        public string $issuedOn,
        public string $verificationUrl,
        public string $qrSvg,
        public ?string $photoDataUri,
        public ?array $terms,
        public ?string $cumulativeGpa,
        public ?string $earnedCredits,
    ) {}
}
