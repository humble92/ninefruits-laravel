<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold">{{ $section->label() }} {{ $section->version->title }}</h1>
            <p class="text-sm text-zinc-600">{{ __('attendance.portal.intro', ['held' => $summary['held'], 'students' => $enrollments->count()]) }}</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('sections.roster.csv', $section) }}" class="rounded border border-zinc-300 px-3 py-1">{{ __('attendance.portal.roster_csv') }}</a>
            <a href="{{ route('sections.attendance.sheet', $section) }}" target="_blank" class="rounded border border-zinc-300 px-3 py-1">{{ __('attendance.portal.sheet_pdf') }}</a>
        </div>
    </div>

    @if ($message)
        <div role="status" @class(['rounded-lg border p-3 text-sm', 'border-emerald-300 bg-emerald-50' => $messageTone === 'success', 'border-red-300 bg-red-50' => $messageTone === 'error'])>{{ $message }}</div>
    @endif

    @if ($meeting)
        <form wire:submit="save" class="space-y-3 rounded-lg border border-zinc-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium">{{ __('attendance.portal.entry_for', ['date' => $meeting->meeting_date->isoFormat('LL'), 'time' => $meeting->timeLabel()]) }}</h2>
                <div class="flex flex-wrap gap-2 text-sm">
                    @if ($canEdit)
                        <button type="button" wire:click="markAll('present')" class="rounded border border-zinc-300 px-2 py-1">{{ __('attendance.portal.mark_all_present') }}</button>
                    @endif
                    <button type="button" wire:click="open(null)" class="underline">{{ __('evaluation.portal.back') }}</button>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left">
                    <tr>
                        <th class="px-3 py-2">{{ __('people.fields.student_number') }}</th>
                        <th class="px-3 py-2">{{ __('people.fields.name') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.status') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($enrollments as $enrollment)
                        <tr class="border-t border-zinc-100" wire:key="row-{{ $enrollment->id }}">
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->student_number }}</td>
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->person->displayName() }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($statusOptions as $option)
                                        <label class="flex items-center gap-1"><input type="radio" wire:model="statuses.{{ $enrollment->id }}" value="{{ $option->value }}" name="status-{{ $enrollment->id }}" @disabled(! $canEdit)> {{ $option->label() }}</label>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-3 py-2"><input type="text" wire:model="notes.{{ $enrollment->id }}" maxlength="200" class="w-full rounded border border-zinc-300 px-2 py-1" @disabled(! $canEdit)></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($canEdit)
                <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('attendance.portal.save') }}</button>
            @endif
        </form>
    @else
        <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
            <h2 class="px-4 pt-4 font-medium">{{ __('attendance.portal.roster') }}</h2>
            <table class="mt-2 w-full text-sm">
                <thead class="bg-zinc-50 text-left">
                    <tr>
                        <th class="px-3 py-2"></th>
                        <th class="px-3 py-2">{{ __('people.fields.student_number') }}</th>
                        <th class="px-3 py-2">{{ __('people.fields.name') }}</th>
                        <th class="px-3 py-2">{{ __('reference.resources.programs.label') }}</th>
                        <th class="px-3 py-2">{{ __('people.fields.year_level') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.statuses.present') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.statuses.late') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.statuses.absent') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.statuses.excused') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        @php($counts = $summary['students'][$enrollment->id])
                        <tr class="border-t border-zinc-100" wire:key="roster-{{ $enrollment->id }}">
                            <td class="px-3 py-1">
                                @if ($enrollment->studentRecord->person->hasMedia(\App\Models\Person\Person::PHOTO_COLLECTION))
                                    <img src="{{ route('people.photo', $enrollment->studentRecord->person) }}" alt="" class="h-10 w-8 rounded object-cover">
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->student_number }}</td>
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->person->displayName() }}</td>
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->program->name }}</td>
                            <td class="px-3 py-2">{{ $enrollment->studentRecord->year_level }}</td>
                            <td class="px-3 py-2">{{ $counts['present'] }}</td>
                            <td class="px-3 py-2">{{ $counts['late'] }}</td>
                            <td class="px-3 py-2">{{ $counts['absent'] }}</td>
                            <td class="px-3 py-2">{{ $counts['excused'] }}</td>
                            <td class="px-3 py-2">{{ $counts['rate'] === null ? '-' : $counts['rate'].'%' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-6 text-center text-zinc-500">{{ __('attendance.portal.no_students') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium">{{ __('attendance.portal.meetings') }}</h2>
                @if ($canEdit)
                    <button type="button" wire:click="generate" class="rounded border border-zinc-300 px-3 py-1 text-sm">{{ __('attendance.portal.generate') }}</button>
                @endif
            </div>
            <table class="mt-2 w-full text-sm">
                <thead class="bg-zinc-50 text-left">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.meeting_date') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.time') }}</th>
                        <th class="px-3 py-2">{{ __('reference.resources.rooms.label') }}</th>
                        <th class="px-3 py-2">{{ __('attendance.fields.recorded') }}</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($meetings as $item)
                        <tr @class(['border-t border-zinc-100', 'text-zinc-400 line-through' => $item->status === \App\Domain\Attendance\Enums\ClassMeetingStatus::Cancelled]) wire:key="meeting-{{ $item->id }}">
                            <td class="px-3 py-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">{{ $item->meeting_date->isoFormat('ll (ddd)') }}</td>
                            <td class="px-3 py-2">{{ $item->timeLabel() }}</td>
                            <td class="px-3 py-2">{{ $item->room ? $item->room->building->code.'-'.$item->room->code : '-' }}</td>
                            <td class="px-3 py-2">{{ $item->status === \App\Domain\Attendance\Enums\ClassMeetingStatus::Cancelled ? $item->cancel_reason : $item->records_count.' / '.$enrollments->count() }}</td>
                            <td class="px-3 py-2 text-right">
                                @if ($item->status === \App\Domain\Attendance\Enums\ClassMeetingStatus::Cancelled)
                                    @if ($canEdit)<button type="button" wire:click="restoreMeeting({{ $item->id }})" class="underline">{{ __('attendance.portal.restore') }}</button>@endif
                                @else
                                    <button type="button" wire:click="open({{ $item->id }})" class="underline">{{ __('attendance.portal.enter') }}</button>
                                    @if ($canEdit)
                                        · <button type="button" x-data x-on:click="const r = prompt(@js(__('attendance.portal.cancel_reason'))); if (r) $wire.cancelMeeting({{ $item->id }}, r)" class="underline">{{ __('attendance.portal.cancel') }}</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">{{ __('attendance.portal.no_meetings') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($canEdit)
                <form wire:submit="addMeeting" class="mt-3 flex flex-wrap items-end gap-2 text-sm">
                    <label>{{ __('attendance.fields.meeting_date') }} <input type="date" wire:model="newDate" class="rounded border border-zinc-300 px-2 py-1"></label>
                    <label>{{ __('attendance.fields.start_time') }} <input type="time" wire:model="newStart" class="rounded border border-zinc-300 px-2 py-1"></label>
                    <label>{{ __('attendance.fields.end_time') }} <input type="time" wire:model="newEnd" class="rounded border border-zinc-300 px-2 py-1"></label>
                    <button type="submit" class="rounded border border-zinc-300 px-3 py-1">{{ __('attendance.portal.add_meeting') }}</button>
                </form>
            @endif
        </section>
    @endif
</div>
