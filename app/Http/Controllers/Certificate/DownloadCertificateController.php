<?php

namespace App\Http\Controllers\Certificate;

use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Certificate\Issues\CertificateIssueResource;
use App\Models\Certificate\CertificateIssue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an issued certificate's stored PDF to staff who may see it and to the holder.
 */
class DownloadCertificateController
{
    public function __invoke(Request $request, CertificateIssue $certificateIssue): Response|StreamedResponse
    {
        Gate::authorize('view', $certificateIssue);
        abort_if($certificateIssue->status === CertificateIssueStatus::Revoked && ! $request->user()?->can(Permission::CertificatesIssue->value), 403);
        $stream = $certificateIssue->getFirstMedia(CertificateIssue::PDF_COLLECTION)?->stream();

        if (! is_resource($stream)) {
            abort_if($request->expectsJson(), 404);

            $canOpenLedger = $request->user()?->can(Permission::AdminPanelAccess->value)
                && Gate::allows('viewAny', CertificateIssue::class);

            return response()->view('errors.notice', [
                'icon' => 'search',
                'title' => __('errors.certificate_pdf.title'),
                'message' => __('errors.certificate_pdf.message'),
                'linkUrl' => $canOpenLedger ? CertificateIssueResource::getUrl('index', panel: 'admin') : route('portal.dashboard'),
                'linkLabel' => $canOpenLedger ? __('certificate.resources.issues.plural_label') : __('portal.navigation.dashboard'),
            ], headers: ['Cache-Control' => 'private, no-store']);
        }

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$certificateIssue->serial_number.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
