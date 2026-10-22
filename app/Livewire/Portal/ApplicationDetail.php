<?php

namespace App\Livewire\Portal;

use App\Domain\Admission\Actions\AttachApplicationDocument;
use App\Domain\Admission\Actions\RespondToOffer;
use App\Domain\Admission\Actions\SubmitApplication;
use App\Domain\Admission\Actions\UpdateApplicantPersonalDetails;
use App\Domain\Admission\Actions\UpdateApplication;
use App\Domain\Admission\Actions\WithdrawApplication;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Person\Enums\Gender;
use App\Models\Admission\Application;
use App\Models\Institution\Country;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One application: the form while it is a draft, then its status, the released decision
 * and the offer response.
 */
#[Layout('components.layouts.portal')]
class ApplicationDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $applicationId;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, mixed> */
    public array $personal = [];

    /** @var array<int|string, TemporaryUploadedFile|null> Upload per document type id */
    public array $uploads = [];

    public string $withdrawReason = '';

    public function mount(Application $application): void
    {
        abort_unless($application->isOwnedBy($this->user()), 404);

        $this->applicationId = $application->id;
        $this->form = [
            ...$application->only(Application::APPLICANT_FIELDS),
            'previous_graduation_date' => $application->previous_graduation_date?->toDateString(),
        ];

        $person = $application->person;
        $this->personal = [
            ...$person->only(UpdateApplicantPersonalDetails::FIELDS),
            'birth_date' => $person->birth_date?->toDateString(),
            'gender' => $person->gender?->value,
        ];
    }

    public function save(UpdateApplication $update, UpdateApplicantPersonalDetails $personalDetails): void
    {
        $application = $this->application();

        $this->validate([
            'form.program_id' => ['required', 'integer'],
            'form.previous_school_name' => ['nullable', 'string', 'max:255'],
            'form.previous_school_country_id' => ['nullable', 'integer'],
            'form.previous_graduation_date' => ['nullable', 'date'],
            'form.personal_statement' => ['nullable', 'string', 'max:10000'],
        ]);

        if (UpdateApplicantPersonalDetails::canEditIdentity($application->person)) {
            $personalDetails->handle($this->user(), $this->personal);
        }

        $update->handle($application, array_map(fn (mixed $value) => $value === '' ? null : $value, $this->form), $this->user());
        session()->flash('status', __('admissions.portal.saved'));
    }

    public function updatedUploads(mixed $file, int|string $documentTypeId): void
    {
        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        $this->validate(["uploads.{$documentTypeId}" => ['file', 'mimes:pdf,jpg,jpeg,png']]);
        app(AttachApplicationDocument::class)->handle($this->application(), (int) $documentTypeId, $file, $this->user());
        unset($this->uploads[$documentTypeId]);
    }

    public function submit(UpdateApplication $update, UpdateApplicantPersonalDetails $personalDetails, SubmitApplication $submit): void
    {
        $this->save($update, $personalDetails);
        $submit->handle($this->application()->refresh(), $this->user());
        session()->flash('status', __('admissions.portal.submitted'));
    }

    public function respond(bool $accept, RespondToOffer $respond): void
    {
        $respond->handle($this->application(), $accept, $this->user());
        session()->flash('status', $accept ? __('admissions.portal.offer_accepted') : __('admissions.portal.offer_declined'));
    }

    public function withdraw(WithdrawApplication $withdraw): void
    {
        $withdraw->handle($this->application(), $this->user(), $this->withdrawReason !== '' ? $this->withdrawReason : null);
        session()->flash('status', __('admissions.portal.withdrawn'));
    }

    public function render(): View
    {
        $application = $this->application()->load(['cycle.programs.program', 'cycle.documentTypes.documentType', 'program', 'studentRecord', 'person']);
        $documents = $application->getMedia(Application::DOCUMENTS_COLLECTION)
            ->groupBy(fn (Media $media) => (int) $media->getCustomProperty('document_type_id'));

        return view('livewire.portal.application-detail', [
            'application' => $application,
            'status' => $application->applicantStatus(),
            'editable' => $application->status === ApplicationStatus::Draft,
            'identityEditable' => UpdateApplicantPersonalDetails::canEditIdentity($application->person),
            'documents' => $documents,
            'countries' => Country::query()->where('is_active', true)->get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'genders' => Gender::cases(),
        ])->title($application->cycle->name);
    }

    private function application(): Application
    {
        return Application::query()->where('person_id', $this->user()->person_id)->findOrFail($this->applicationId);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
