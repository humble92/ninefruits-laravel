@php($enrolled = \App\Domain\Registration\Enums\EnrollmentStatus::Enrolled)
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('registration.portal.title') }}</h1>
            <p class="text-sm text-zinc-600">{{ $record->student_number }} · {{ $record->program->name }}</p>
        </div>
        @if ($terms->isNotEmpty())
            <select wire:model.live="termId" aria-label="{{ __('reference.resources.terms.label') }}" class="rounded border border-zinc-300 px-3 py-2">
                @foreach ($terms as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if ($messages !== [])
        <div role="status" @class([
            'rounded-lg border p-3 text-sm',
            'border-emerald-300 bg-emerald-50' => $messageTone === 'success',
            'border-red-300 bg-red-50' => $messageTone === 'error',
            'border-amber-300 bg-amber-50' => $messageTone === 'warning',
            'border-zinc-200 bg-white' => $messageTone === 'info',
        ])>
            @foreach ($messages as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif
    @error('registration') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    @if ($term === null)
        <p class="text-sm text-zinc-600">{{ __('registration.portal.no_term') }}</p>
    @else
        <section class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 text-sm sm:grid-cols-3">
            <p>
                <span class="text-zinc-500">{{ __('registration.portal.window') }}</span><br>
                @if ($registrationOpen)
                    {{ __('registration.portal.registration_open') }}
                @elseif ($cartOpen)
                    {{ __('registration.portal.cart_open') }}
                @elseif ($nextWindow)
                    {{ __('registration.portal.opens_at', ['date' => $nextWindow->opens_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL')]) }}
                @else
                    {{ __('registration.portal.closed') }}
                @endif
            </p>
            <p>
                <span class="text-zinc-500">{{ __('registration.portal.credits') }}</span><br>
                {{ $credits }}@if ($limit) / {{ (float) $limit }}@endif
            </p>
            <p>
                <span class="text-zinc-500">{{ __('registration.portal.sections_count') }}</span><br>
                {{ $activeCount }}
            </p>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-4">
            <h2 class="mb-3 font-medium">{{ __('registration.portal.timetable') }}</h2>
            <x-timetable :timetable="$timetable" />
        </section>

        @include('livewire.portal.partials.registration-lists')
    @endif
</div>
