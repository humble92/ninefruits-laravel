<?php

namespace App\Http\Controllers\Certificate;

use App\Domain\Certificate\Services\CertificateVerification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public verification page (the QR code on every certificate points here). No sign-in,
 * rate limited, and only what the paper shows.
 */
class VerifyCertificateController
{
    public function __invoke(Request $request, CertificateVerification $verification, ?string $code = null): View
    {
        $code = $code ?? (string) $request->query('code', '');

        return view('certificates.verify', ['code' => $code, 'result' => $code === '' ? null : $verification->check($code)]);
    }
}
