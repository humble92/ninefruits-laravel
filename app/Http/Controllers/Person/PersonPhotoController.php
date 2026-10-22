<?php

namespace App\Http\Controllers\Person;

use App\Models\Person\Person;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a person's photo from the private disk to users who may see the person.
 */
class PersonPhotoController
{
    public function __invoke(Person $person): StreamedResponse
    {
        Gate::authorize('view', $person);

        $media = $person->getFirstMedia(Person::PHOTO_COLLECTION) ?? abort(404);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
