<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('portal.profile.title') }}</h1>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <dl class="mb-6 grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
            <dt class="text-zinc-500">{{ __('people.fields.name') }}</dt><dd>{{ $person->displayName() }} ({{ $person->romanizedName() }})</dd>
            <dt class="text-zinc-500">{{ __('people.fields.birth_date') }}</dt><dd>{{ $person->birth_date?->toDateString() ?? '-' }}</dd>
        </dl>
        <p class="mb-4 text-sm text-zinc-600">{{ __('portal.profile.registrar_fields') }}</p>

        <form wire:submit="saveContact" class="grid gap-4 sm:grid-cols-2">
            @foreach (['email' => 'contact_email', 'phone' => 'phone', 'postal_code' => 'postal_code'] as $field => $label)
                <div>
                    <label for="contact-{{ $field }}" class="block text-sm font-medium">{{ __('people.fields.'.$label) }}</label>
                    <input id="contact-{{ $field }}" type="text" wire:model="contact.{{ $field }}" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                    @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <label for="contact-address" class="block text-sm font-medium">{{ __('people.fields.address') }}</label>
                <textarea id="contact-address" wire:model="contact.address" rows="2" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
            </div>
            <div><button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('common.save') }}</button></div>
        </form>
    </section>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <h2 class="mb-3 font-medium">{{ __('people.resources.guardians.plural_label') }}</h2>
        @foreach ($guardians as $item)
            <p class="flex items-center justify-between border-b border-zinc-100 py-2 text-sm">
                <span>{{ $item->relationship->label() }} · {{ $item->full_name }} · {{ $item->phone ?? '-' }}</span>
                <button type="button" wire:click="removeGuardian({{ $item->id }})" wire:confirm="{{ __('portal.profile.remove_confirm') }}" class="text-red-700 underline">{{ __('portal.profile.remove') }}</button>
            </p>
        @endforeach

        <form wire:submit="addGuardian" class="mt-4 grid gap-3 sm:grid-cols-4">
            <select wire:model="guardian.relationship" aria-label="{{ __('people.fields.relationship') }}" class="rounded border border-zinc-300 px-3 py-2">
                @foreach ($relationships as $relationship)
                    <option value="{{ $relationship->value }}">{{ $relationship->label() }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="guardian.full_name" placeholder="{{ __('people.fields.name') }}" class="rounded border border-zinc-300 px-3 py-2">
            <input type="text" wire:model="guardian.phone" placeholder="{{ __('people.fields.phone') }}" class="rounded border border-zinc-300 px-3 py-2">
            <button type="submit" class="rounded border border-zinc-300 px-3 py-2 text-sm">{{ __('portal.profile.add_guardian') }}</button>
            @error('guardian.full_name') <p class="text-sm text-red-600 sm:col-span-4">{{ $message }}</p> @enderror
        </form>
    </section>
</div>
