<?php

namespace App\Http\Controllers\Admission;

use App\Models\Admission\Application;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an uploaded application document from the private disk to the applicant and to
 * staff who may see the application.
 */
class ApplicationDocumentController
{
    public function __invoke(Application $application, Media $media): StreamedResponse
    {
        Gate::authorize('view', $application);

        abort_unless($media->model_type === $application->getMorphClass() && (int) $media->model_id === $application->id, 404);

        return response()->streamDownload(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, $media->file_name, ['Content-Type' => $media->mime_type, 'Cache-Control' => 'private, no-store']);
    }
}
