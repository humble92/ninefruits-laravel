<div>
    @if ($timetable === null)
        <p class="text-sm text-gray-500">{{ __('offering.timetable.choose') }}</p>
    @else
        <x-timetable :timetable="$timetable" />
    @endif
</div>
