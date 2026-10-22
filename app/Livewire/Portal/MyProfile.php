<?php

namespace App\Livewire\Portal;

use App\Domain\Person\Actions\UpdateOwnContactDetails;
use App\Domain\Person\Enums\GuardianRelationship;
use App\Models\Person\Guardian;
use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A person's own contact details and guardians. Names, birth date and identity numbers are
 * changed by the registrar.
 */
#[Layout('components.layouts.portal')]
class MyProfile extends Component
{
    /** @var array<string, string|null> */
    public array $contact = [];

    /** @var array<string, mixed> */
    public array $guardian = ['relationship' => 'mother', 'full_name' => '', 'phone' => '', 'email' => '', 'is_primary_contact' => false];

    public function mount(): void
    {
        $person = $this->person();
        $this->contact = $person->only(Person::SELF_EDITABLE);
    }

    public function saveContact(UpdateOwnContactDetails $update): void
    {
        $update->handle($this->user(), $this->contact);
        session()->flash('status', __('portal.profile.saved'));
    }

    public function addGuardian(): void
    {
        $data = $this->validate([
            'guardian.relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            'guardian.full_name' => ['required', 'string', 'max:255'],
            'guardian.phone' => ['nullable', 'string', 'max:32'],
            'guardian.email' => ['nullable', 'email', 'max:255'],
            'guardian.is_primary_contact' => ['boolean'],
        ])['guardian'];

        Guardian::query()->create([...$data, 'person_id' => $this->user()->person_id]);
        $this->reset('guardian');
    }

    public function removeGuardian(int $guardianId): void
    {
        Guardian::query()->where('person_id', $this->user()->person_id)->findOrFail($guardianId)->delete();
    }

    public function render(): View
    {
        $person = $this->person();

        return view('livewire.portal.my-profile', [
            'person' => $person,
            'guardians' => $person->guardians()->get(),
            'relationships' => GuardianRelationship::cases(),
        ])->title(__('portal.profile.title'));
    }

    private function person(): Person
    {
        abort_if($this->user()->person_id === null, 404);

        return Person::query()->findOrFail($this->user()->person_id);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
