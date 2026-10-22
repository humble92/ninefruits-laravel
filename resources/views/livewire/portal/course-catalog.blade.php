<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('offering.catalog.title') }}</h1>

    <section class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-3">
        <div>
            <label for="term" class="block text-sm font-medium">{{ __('reference.resources.terms.label') }}</label>
            <select id="term" wire:model.live="termId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="unit" class="block text-sm font-medium">{{ __('offering.fields.offering_unit') }}</label>
            <select id="unit" wire:model.live="orgUnitId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                <option value="">{{ __('offering.catalog.all_units') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="search" class="block text-sm font-medium">{{ __('offering.catalog.search') }}</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('offering.catalog.search_placeholder') }}" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
        </div>
    </section>

    <section class="space-y-2">
        @forelse ($sections as $section)
            <a href="{{ route('portal.sections.show', $section) }}" class="block rounded-lg border border-zinc-200 bg-white p-4 hover:border-zinc-400">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-medium">{{ $section->label() }} {{ $section->version->title }}</span>
                    <span class="text-sm text-zinc-600">{{ __('offering.catalog.credits', ['credits' => $section->version->credits]) }} · {{ $section->version->category?->name }}</span>
                </div>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $section->instructors->map(fn ($instructor) => $instructor->person->displayName())->implode(', ') ?: __('offering.catalog.instructor_tba') }}
                    · {{ \App\Domain\Offering\Services\Timetable::summary($section->meetings) ?: __('offering.catalog.time_tba') }}
                    @unless ($section->session->is_full_term) · {{ $section->session->name }} ({{ $section->session->start_date->format('m-d') }}~{{ $section->session->end_date->format('m-d') }}) @endunless
                    @if ($section->syllabus?->published_at) · {{ __('offering.syllabus.available') }} @endif
                </p>
            </a>
        @empty
            <p class="text-sm text-zinc-500">{{ __('offering.catalog.none') }}</p>
        @endforelse
    </section>

    {{ $sections->links() }}
</div>
