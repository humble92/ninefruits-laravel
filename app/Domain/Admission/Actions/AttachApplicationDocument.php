<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Settings\AdmissionSettings;
use App\Models\Admission\Application;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Uploads a document of a type the cycle asks for. A new upload of the same type replaces
 * the earlier one while the application is a draft.
 */
class AttachApplicationDocument
{
    public function __construct(private readonly AdmissionSettings $settings) {}

    public function handle(Application $application, int $documentTypeId, UploadedFile $file, User $applicant): Media
    {
        UpdateApplication::ensureEditableBy($application, $applicant);

        if (! $application->cycle->documentTypes()->where('document_type_id', $documentTypeId)->exists()) {
            throw ValidationException::withMessages(['document_type_id' => __('admissions.errors.document_type_not_requested')]);
        }

        if ($file->getSize() > $this->settings->max_document_size_mb * 1024 * 1024) {
            throw ValidationException::withMessages(['document' => __('admissions.errors.document_too_large', ['mb' => $this->settings->max_document_size_mb])]);
        }

        foreach (self::documentsOfType($application, $documentTypeId) as $existing) {
            $existing->delete();
        }

        try {
            return $application->addMedia($file)
                ->withCustomProperties(['document_type_id' => $documentTypeId])
                ->toMediaCollection(Application::DOCUMENTS_COLLECTION);
        } catch (FileUnacceptableForCollection) {
            throw ValidationException::withMessages(['document' => __('admissions.errors.document_type_invalid')]);
        }
    }

    /**
     * @return list<Media>
     */
    public static function documentsOfType(Application $application, int $documentTypeId): array
    {
        return array_values($application->getMedia(Application::DOCUMENTS_COLLECTION)
            ->filter(fn (Media $media) => (int) $media->getCustomProperty('document_type_id') === $documentTypeId)
            ->all());
    }
}
