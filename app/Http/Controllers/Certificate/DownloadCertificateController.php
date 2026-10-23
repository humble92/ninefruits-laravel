<?php

namespace App\Http\Controllers\Certificate;

use App\Models\Certificate\CertificateIssue;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an issued certificate's stored PDF to staff who may see it and to the holder.
 */
class DownloadCertificateController
{
    public function __invoke(CertificateIssue $certificateIssue): StreamedResponse
    {
        Gate::authorize('view', $certificateIssue);
        $media = $certificateIssue->getFirstMedia(CertificateIssue::PDF_COLLECTION) ?? abort(404);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$certificateIssue->serial_number.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
