<section class="rounded-lg border border-zinc-200 bg-white">
    <h2 class="border-b border-zinc-100 px-4 py-3 font-medium">{{ __('registration.portal.my_registrations') }}</h2>
    @forelse ($enrollments as $enrollment)
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-2 text-sm last:border-0">
            <span>
                {{ $enrollment->section->label() }} {{ $enrollment->section->version->title }} · {{ (float) $enrollment->credits }}
                @if ($enrollment->is_retake)
                    <span class="rounded bg-zinc-100 px-1 text-xs">{{ __('registration.portal.retake') }}</span>
                @endif
                @if ($enrollment->status !== $enrolled)
                    <span class="rounded bg-zinc-100 px-1 text-xs">{{ $enrollment->status->label() }}</span>
                @endif
                <span class="block text-xs text-zinc-500">{{ \App\Domain\Offering\Services\Timetable::summary($enrollment->section->meetings) }}</span>
            </span>
            @if ($enrollment->status === $enrolled)
                @if ($canDrop($enrollment))
                    <button type="button" wire:click="drop({{ $enrollment->id }})" wire:confirm="{{ __('registration.portal.drop_confirm') }}" wire:loading.attr="disabled" class="text-red-700 underline">{{ __('registration.portal.drop') }}</button>
                @elseif ($canWithdraw($enrollment))
                    <button type="button" wire:click="drop({{ $enrollment->id }}, true)" wire:confirm="{{ __('registration.portal.withdraw_confirm') }}" wire:loading.attr="disabled" class="text-red-700 underline">{{ __('registration.portal.withdraw') }}</button>
                @endif
            @endif
        </div>
    @empty
        <p class="px-4 py-3 text-sm text-zinc-500">{{ __('common.none') }}</p>
    @endforelse

    @foreach ($waitlist as $entry)
        <div class="flex items-center justify-between border-t border-zinc-100 px-4 py-2 text-sm">
            <span>{{ $entry->section->label() }} · {{ __('registration.portal.waiting', ['position' => $entry->position()]) }}</span>
            <button type="button" wire:click="leaveWaitlist({{ $entry->id }})" class="underline">{{ __('registration.portal.leave_waitlist') }}</button>
        </div>
    @endforeach
</section>

<section class="rounded-lg border border-zinc-200 bg-white">
    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
        <h2 class="font-medium">{{ __('registration.portal.cart') }}</h2>
        @if ($registrationOpen && $cart->isNotEmpty())
            <button type="button" wire:click="submitCart" wire:loading.attr="disabled" class="rounded bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white">{{ __('registration.portal.submit_cart') }}</button>
        @endif
    </div>
    @forelse ($cart as $item)
        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2 text-sm last:border-0">
            <span>{{ $item->section->label() }} {{ $item->section->version->title }} · {{ (float) $item->section->version->credits }}</span>
            <button type="button" wire:click="removeFromCart({{ $item->section_id }})" class="underline">{{ __('portal.profile.remove') }}</button>
        </div>
    @empty
        <p class="px-4 py-3 text-sm text-zinc-500">{{ __('registration.portal.cart_empty') }}</p>
    @endforelse
</section>

<section class="rounded-lg border border-zinc-200 bg-white p-4">
    <label for="search" class="block text-sm font-medium">{{ __('offering.catalog.search') }}</label>
    <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('offering.catalog.search_placeholder') }}" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">

    <div class="mt-3 space-y-2">
        @foreach ($results as $section)
            @php($free = $seats($section))
            <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-zinc-200 p-3 text-sm">
                <span>
                    <span class="font-medium">{{ $section->label() }} {{ $section->version->title }}</span> · {{ (float) $section->version->credits }}
                    <span class="block text-xs text-zinc-500">
                        {{ $section->instructors->map(fn ($instructor) => $instructor->person->displayName())->implode(', ') ?: __('offering.catalog.instructor_tba') }}
                        · {{ \App\Domain\Offering\Services\Timetable::summary($section->meetings) ?: __('offering.catalog.time_tba') }}
                    </span>
                    <span class="block text-xs text-zinc-500">{{ __('registration.portal.seats', ['general' => $free['general'], 'reserved' => $free['reserved'], 'capacity' => $section->capacity]) }}</span>
                </span>
                <span class="flex gap-2">
                    @if ($cartOpen || $registrationOpen)
                        <button type="button" wire:click="addToCart({{ $section->id }})" wire:loading.attr="disabled" class="rounded border border-zinc-300 px-3 py-1.5">{{ __('registration.portal.add_to_cart') }}</button>
                    @endif
                    <button type="button" wire:click="enroll({{ $section->id }})" wire:loading.attr="disabled" class="rounded bg-zinc-900 px-3 py-1.5 font-medium text-white">{{ __('registration.portal.register') }}</button>
                    @if ($section->waitlist_capacity > $section->waiting_count && $free['general'] === 0)
                        <button type="button" wire:click="enroll({{ $section->id }}, true)" wire:loading.attr="disabled" class="rounded border border-zinc-300 px-3 py-1.5">{{ __('registration.portal.join_waitlist') }}</button>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</section>
