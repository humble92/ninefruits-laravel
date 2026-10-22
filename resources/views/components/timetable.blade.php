@props(['timetable'])
@php($columns = count($timetable['weekdays']))
@php($toTime = fn (int $minutes) => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60))

@if ($timetable['entries'] === [])
    <p class="text-sm text-zinc-500">{{ __('offering.timetable.empty') }}</p>
@else
    {{-- Phones: a list per weekday --}}
    <div class="space-y-3 sm:hidden">
        @foreach ($timetable['weekdays'] as $weekday)
            @php($dayEntries = array_filter($timetable['entries'], fn ($entry) => $entry['weekday'] === $weekday))
            @if ($dayEntries !== [])
                <section>
                    <h3 class="mb-1 text-sm font-medium">{{ \App\Domain\Offering\Services\Timetable::weekdayLabel($weekday) }}</h3>
                    @foreach ($dayEntries as $entry)
                        <div class="mb-1 rounded border border-zinc-200 bg-white px-3 py-2 text-sm">
                            <span class="text-zinc-500">{{ $entry['time'] }}</span> {{ $entry['title'] }}
                            <span class="block text-xs text-zinc-500">{{ $entry['subtitle'] }}</span>
                        </div>
                    @endforeach
                </section>
            @endif
        @endforeach
    </div>

    {{-- Larger screens: a weekly grid --}}
    <div class="hidden overflow-x-auto sm:block">
        <div class="grid min-w-[40rem] text-xs" style="grid-template-columns: 4rem repeat({{ $columns }}, minmax(0, 1fr)); grid-template-rows: 2rem repeat({{ count($timetable['rows']) }}, 1.75rem);">
            <div></div>
            @foreach ($timetable['weekdays'] as $index => $weekday)
                <div class="border-b border-zinc-200 px-2 py-1 text-center font-medium" style="grid-column: {{ $index + 2 }}; grid-row: 1;">{{ \App\Domain\Offering\Services\Timetable::weekdayLabel($weekday) }}</div>
            @endforeach
            @foreach ($timetable['rows'] as $row => $minute)
                <div class="border-t border-zinc-100 pe-2 text-end text-zinc-500" style="grid-column: 1; grid-row: {{ $row + 2 }};">{{ $minute % 60 === 0 ? $toTime($minute) : '' }}</div>
                @foreach ($timetable['weekdays'] as $index => $weekday)
                    <div class="border-t border-zinc-100" style="grid-column: {{ $index + 2 }}; grid-row: {{ $row + 2 }};"></div>
                @endforeach
            @endforeach
            @foreach ($timetable['entries'] as $entry)
                <div class="m-0.5 overflow-hidden rounded border border-sky-300 bg-sky-50 px-2 py-1" title="{{ $entry['title'] }} {{ $entry['time'] }}"
                     style="grid-column: {{ array_search($entry['weekday'], $timetable['weekdays'], true) + 2 }}; grid-row: {{ $entry['top'] + 2 }} / span {{ $entry['span'] }};">
                    <span class="font-medium">{{ $entry['title'] }}</span>
                    <span class="block text-zinc-600">{{ $entry['time'] }}</span>
                    <span class="block text-zinc-500">{{ $entry['subtitle'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
