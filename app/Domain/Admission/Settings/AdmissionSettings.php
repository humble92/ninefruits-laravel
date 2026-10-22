<?php

namespace App\Domain\Admission\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Admission options (settings group `admissions`).
 */
class AdmissionSettings extends Settings
{
    /** Whether people can create an account themselves to apply. */
    public bool $applicant_registration_enabled;

    /**
     * Drafts not touched for this many days expire (admission:expire-drafts). Drafts in a
     * cycle that has closed expire regardless.
     */
    public int $draft_expiry_days;

    /** Largest uploaded document, in megabytes. */
    public int $max_document_size_mb;

    public static function group(): string
    {
        return 'admissions';
    }
}
