<?php

namespace App\Http\Controllers\Assessment;

use App\Models\Assessment\AssessmentResult;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The evidence file of an external result, for staff who record and for the student.
 */
class EvidenceController
{
    public function __invoke(AssessmentResult $assessmentResult): StreamedResponse
    {
        Gate::authorize('view', $assessmentResult);
        $media = $assessmentResult->getFirstMedia(AssessmentResult::EVIDENCE_COLLECTION) ?? abort(404);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
