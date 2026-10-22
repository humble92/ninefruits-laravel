<div class="space-y-6">
    <div>
        <a href="{{ route('portal.applications') }}" class="text-sm text-zinc-600 underline">{{ __('admissions.portal.title') }}</a>
        <h1 class="mt-1 text-2xl font-semibold">{{ $application->cycle->name }}</h1>
        <p class="text-sm text-zinc-600">
            {{ __('admissions.fields.status') }}: <strong>{{ $status->label() }}</strong>
            @if ($application->application_number) · {{ __('admissions.fields.application_number') }} {{ $application->application_number }} @endif
        </p>
    </div>

    @if ($application->studentRecord)
        <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm">
            {{ __('admissions.portal.matriculated', ['number' => $application->studentRecord->student_number]) }}
            <a href="{{ route('portal.records') }}" class="ms-2 underline">{{ __('portal.navigation.records') }}</a>
        </section>
    @elseif ($status === \App\Domain\Admission\Enums\ApplicationStatus::Admitted)
        <section class="space-y-3 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm">
            <p class="font-medium">{{ __('admissions.portal.admitted', ['program' => $application->program->name]) }}</p>
            @if ($application->offer_expires_at)
                <p>{{ __('admissions.portal.respond_by', ['date' => $application->offer_expires_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL')]) }}</p>
            @endif
            <div class="flex gap-3">
                <button type="button" wire:click="respond(true)" wire:confirm="{{ __('admissions.portal.accept_confirm') }}" class="rounded bg-zinc-900 px-4 py-2 font-medium text-white">{{ __('admissions.portal.accept') }}</button>
                <button type="button" wire:click="respond(false)" wire:confirm="{{ __('admissions.portal.decline_confirm') }}" class="rounded border border-zinc-300 bg-white px-4 py-2">{{ __('admissions.portal.decline') }}</button>
            </div>
            @error('status') <p class="text-red-600">{{ $message }}</p> @enderror
        </section>
    @elseif ($status === \App\Domain\Admission\Enums\ApplicationStatus::Confirmed)
        <section class="rounded-lg border border-zinc-200 bg-white p-4 text-sm">{{ __('admissions.portal.confirmed') }}</section>
    @elseif ($status === \App\Domain\Admission\Enums\ApplicationStatus::Rejected)
        <section class="rounded-lg border border-zinc-200 bg-white p-4 text-sm">{{ __('admissions.portal.rejected') }}</section>
    @elseif ($status->isAwaitingDecision())
        <section class="rounded-lg border border-zinc-200 bg-white p-4 text-sm">{{ __('admissions.portal.waiting') }}</section>
    @endif

    @if ($editable)
        <form wire:submit="save" class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <h2 class="mb-4 font-medium">{{ __('admissions.sections.personal') }}</h2>
                @if ($identityEditable)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="family_name" class="block text-sm font-medium">{{ __('people.fields.family_name') }}</label>
                            <input id="family_name" type="text" wire:model="personal.family_name" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                            @error('family_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="given_name" class="block text-sm font-medium">{{ __('people.fields.given_name') }}</label>
                            <input id="given_name" type="text" wire:model="personal.given_name" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                        </div>
                        <div>
                            <label for="birth_date" class="block text-sm font-medium">{{ __('people.fields.birth_date') }}</label>
                            <input id="birth_date" type="date" wire:model="personal.birth_date" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                            @error('birth_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium">{{ __('people.fields.gender') }}</label>
                            <select id="gender" wire:model="personal.gender" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                                <option value="">-</option>
                                @foreach ($genders as $gender)
                                    <option value="{{ $gender->value }}">{{ $gender->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="nationality" class="block text-sm font-medium">{{ __('people.fields.nationality') }}</label>
                            <select id="nationality" wire:model="personal.nationality_country_id" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                                <option value="">-</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium">{{ __('people.fields.phone') }}</label>
                            <input id="phone" type="text" wire:model="personal.phone" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address" class="block text-sm font-medium">{{ __('people.fields.address') }}</label>
                            <textarea id="address" wire:model="personal.address" rows="2" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
                        </div>
                    </div>
                @else
                    <p class="text-sm">{{ $application->person->displayName() }} ({{ $application->person->romanizedName() }})</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('admissions.portal.identity_locked') }} <a href="{{ route('portal.profile') }}" class="underline">{{ __('portal.navigation.profile') }}</a></p>
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <h2 class="mb-4 font-medium">{{ __('admissions.sections.application') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="program_id" class="block text-sm font-medium">{{ __('reference.fields.programs') }}</label>
                        <select id="program_id" wire:model="form.program_id" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                            @foreach ($application->cycle->programs as $offered)
                                <option value="{{ $offered->program_id }}">{{ $offered->program->name }}</option>
                            @endforeach
                        </select>
                        @error('program_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="previous_school_name" class="block text-sm font-medium">{{ __('admissions.fields.previous_school_name') }}</label>
                        <input id="previous_school_name" type="text" wire:model="form.previous_school_name" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                        @error('previous_school_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="previous_school_country_id" class="block text-sm font-medium">{{ __('admissions.fields.previous_school_country') }}</label>
                        <select id="previous_school_country_id" wire:model="form.previous_school_country_id" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                            <option value="">-</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="previous_graduation_date" class="block text-sm font-medium">{{ __('admissions.fields.previous_graduation_date') }}</label>
                        <input id="previous_graduation_date" type="date" wire:model="form.previous_graduation_date" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="personal_statement" class="block text-sm font-medium">{{ __('admissions.fields.personal_statement') }}</label>
                        <textarea id="personal_statement" wire:model="form.personal_statement" rows="8" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
                        @error('personal_statement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <h2 class="mb-4 font-medium">{{ __('admissions.sections.documents') }}</h2>
                @forelse ($application->cycle->documentTypes as $type)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 py-3 text-sm last:border-0">
                        <span>
                            {{ $type->documentType->name }}
                            @if ($type->is_required) <span class="text-red-600">*</span> @endif
                            @foreach ($documents->get($type->document_type_id, collect()) as $media)
                                <a href="{{ route('applications.documents.show', [$application, $media]) }}" class="ms-2 underline">{{ $media->file_name }}</a>
                            @endforeach
                        </span>
                        <input type="file" wire:model="uploads.{{ $type->document_type_id }}" accept=".pdf,.jpg,.jpeg,.png" aria-label="{{ $type->documentType->name }}" class="text-sm">
                        @error('uploads.'.$type->document_type_id) <p class="w-full text-red-600">{{ $message }}</p> @enderror
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('common.none') }}</p>
                @endforelse
                @error('documents') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('document') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded border border-zinc-300 bg-white px-4 py-2 text-sm">{{ __('admissions.portal.save_draft') }}</button>
                <button type="button" wire:click="submit" wire:confirm="{{ __('admissions.portal.submit_confirm') }}" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('admissions.portal.submit') }}</button>
            </div>
            @error('status') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </form>
    @else
        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <dl class="grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
                <dt class="text-zinc-500">{{ __('reference.fields.programs') }}</dt><dd>{{ $application->program->name }}</dd>
                <dt class="text-zinc-500">{{ __('admissions.fields.submitted_at') }}</dt><dd>{{ $application->submitted_at?->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL') ?? '-' }}</dd>
                <dt class="text-zinc-500">{{ __('admissions.fields.previous_school_name') }}</dt><dd>{{ $application->previous_school_name ?? '-' }}</dd>
            </dl>
        </section>
    @endif

    @if ($status->isOpen() && $application->studentRecord === null && $status !== \App\Domain\Admission\Enums\ApplicationStatus::Admitted)
        <details class="rounded-lg border border-zinc-200 bg-white p-4 text-sm">
            <summary class="cursor-pointer">{{ __('admissions.portal.withdraw') }}</summary>
            <div class="mt-3 space-y-2">
                <textarea wire:model="withdrawReason" rows="2" placeholder="{{ __('common.reason') }}" class="w-full rounded border border-zinc-300 px-3 py-2"></textarea>
                <button type="button" wire:click="withdraw" wire:confirm="{{ __('admissions.portal.withdraw_confirm') }}" class="rounded border border-red-300 px-4 py-2 text-red-700">{{ __('admissions.portal.withdraw') }}</button>
            </div>
        </details>
    @endif
</div>
