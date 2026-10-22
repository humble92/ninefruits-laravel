<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('portal.records.title') }}</h1>

    @if ($records->count() > 1)
        <section class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="mb-2 text-sm text-zinc-600">{{ __('portal.records.choose') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($records as $option)
                    <button type="button" wire:click="choose({{ $option->id }})"
                            @class(['rounded border px-3 py-2 text-sm', 'border-zinc-900 bg-zinc-900 text-white' => $option->id === $record?->id, 'border-zinc-300' => $option->id !== $record?->id])>
                        {{ $option->student_number }} · {{ $option->program->name }} · {{ $option->status->label() }}
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if ($record === null)
        <p class="text-sm text-zinc-600">{{ __('portal.records.none') }}</p>
    @else
        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <dl class="grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
                <dt class="text-zinc-500">{{ __('people.fields.student_number') }}</dt><dd>{{ $record->student_number }}</dd>
                <dt class="text-zinc-500">{{ __('reference.fields.programs') }}</dt><dd>{{ $record->program->name }}</dd>
                <dt class="text-zinc-500">{{ __('reference.fields.org_unit') }}</dt><dd>{{ $record->orgUnit->name }}</dd>
                <dt class="text-zinc-500">{{ __('people.fields.status') }}</dt><dd>{{ $record->status->label() }}</dd>
                <dt class="text-zinc-500">{{ __('people.fields.year_level') }}</dt><dd>{{ $record->year_level }}</dd>
                <dt class="text-zinc-500">{{ __('people.fields.admission_date') }}</dt><dd>{{ $record->admission_date->toDateString() }}</dd>
                @if ($record->graduation_date)
                    <dt class="text-zinc-500">{{ __('people.fields.graduation_date') }}</dt><dd>{{ $record->graduation_date->toDateString() }}</dd>
                @endif
                <dt class="text-zinc-500">{{ __('people.fields.advisor') }}</dt>
                <dd>{{ $record->advisorAssignments->filter(fn ($assignment) => $assignment->end_date === null)->map(fn ($assignment) => $assignment->instructor->person->displayName())->implode(', ') ?: __('common.none') }}</dd>
            </dl>
        </section>

        @if ($pending)
            <section class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                {{ __('portal.records.pending', ['type' => $pending->type->name, 'date' => $pending->effective_date->toDateString()]) }}
                <button type="button" wire:click="cancel({{ $pending->id }})" wire:confirm="{{ __('portal.records.cancel_confirm') }}" class="ms-2 underline">{{ __('portal.records.cancel') }}</button>
            </section>
        @elseif ($requestableTypes->isNotEmpty())
            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <h2 class="mb-4 font-medium">{{ __('portal.records.request_title') }}</h2>
                <form wire:submit="requestChange" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="type" class="block text-sm font-medium">{{ __('people.fields.status_change_type') }}</label>
                        <select id="type" wire:model="statusChangeTypeId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                            <option value="">-</option>
                            @foreach ($requestableTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('statusChangeTypeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('status_change_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="effectiveDate" class="block text-sm font-medium">{{ __('people.fields.effective_date') }}</label>
                        <input id="effectiveDate" type="date" wire:model="effectiveDate" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="reason" class="block text-sm font-medium">{{ __('common.reason') }}</label>
                        <textarea id="reason" wire:model="reason" rows="3" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
                        @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div><button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('portal.records.submit_request') }}</button></div>
                </form>
            </section>
        @endif

        <section class="space-y-3 rounded-lg border border-zinc-200 bg-white p-6">
            <h2 class="font-medium">{{ __('people.resources.additional_programs.plural_label') }}</h2>
            @forelse ($record->additionalPrograms as $additional)
                <p class="text-sm">{{ $additional->role->label() }} · {{ $additional->program->name }} · {{ $additional->start_date->toDateString() }} ~ {{ $additional->end_date?->toDateString() }}</p>
            @empty
                <p class="text-sm text-zinc-500">{{ __('common.none') }}</p>
            @endforelse

            @foreach ($additionalRequests as $additionalRequest)
                <p class="text-sm text-zinc-600">
                    {{ __('portal.records.additional_program_request', ['role' => $additionalRequest->role->label(), 'program' => $additionalRequest->program->name, 'status' => $additionalRequest->status->label()]) }}
                    @if ($additionalRequest->decision_note) ({{ $additionalRequest->decision_note }}) @endif
                    @if ($additionalRequest->status === \App\Domain\Person\Enums\AdditionalProgramRequestStatus::Requested)
                        <button type="button" wire:click="cancelAdditionalProgram({{ $additionalRequest->id }})" wire:confirm="{{ __('portal.records.cancel_confirm') }}" class="ms-2 underline">{{ __('portal.records.cancel') }}</button>
                    @endif
                </p>
            @endforeach

            @if ($applicationTerm)
                <form wire:submit="applyAdditionalProgram" class="grid gap-3 border-t border-zinc-100 pt-3 sm:grid-cols-3">
                    <p class="text-sm text-zinc-600 sm:col-span-3">{{ __('portal.records.additional_program_open', ['term' => $applicationTerm->name]) }}</p>
                    <select wire:model.live="additionalRole" aria-label="{{ __('people.fields.program_role') }}" class="rounded border border-zinc-300 px-3 py-2">
                        @foreach (\App\Domain\Person\Enums\AdditionalProgramRole::cases() as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <select wire:model="additionalProgramId" aria-label="{{ __('reference.fields.programs') }}" class="rounded border border-zinc-300 px-3 py-2 sm:col-span-2">
                        <option value="">-</option>
                        @foreach ($additionalOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                    <textarea wire:model="additionalStatement" rows="2" placeholder="{{ __('people.fields.statement') }}" class="rounded border border-zinc-300 px-3 py-2 sm:col-span-3"></textarea>
                    <div class="sm:col-span-3">
                        <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('portal.records.apply_additional_program') }}</button>
                        @foreach (['additionalProgramId', 'program_id', 'role', 'student_record_id'] as $field)
                            @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                </form>
            @endif
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <h2 class="mb-3 font-medium">{{ __('people.resources.status_changes.plural_label') }}</h2>
            @forelse ($record->statusChanges as $change)
                <p class="border-b border-zinc-100 py-2 text-sm last:border-0">
                    {{ $change->effective_date->toDateString() }} · {{ $change->type->name }} · {{ $change->status->label() }}
                    @if ($change->decision_note) <span class="text-zinc-500">({{ $change->decision_note }})</span> @endif
                </p>
            @empty
                <p class="text-sm text-zinc-500">{{ __('common.none') }}</p>
            @endforelse
        </section>
    @endif
</div>
