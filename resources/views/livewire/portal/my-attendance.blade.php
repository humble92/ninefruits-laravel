<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('attendance.portal.my_title') }}</h1>
            <p class="text-sm text-zinc-600">{{ __('attendance.portal.my_intro') }}</p>
        </div>
        <select wire:model.live="termId" class="rounded border border-zinc-300 px-2 py-1 text-sm">
            @foreach ($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($rows as $row)
        <details class="rounded-lg border border-zinc-200 bg-white p-4" wire:key="enrollment-{{ $row['enrollment']->id }}">
            <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2">
                <span class="font-medium">{{ $row['enrollment']->section->label() }} {{ $row['enrollment']->version->title }}</span>
                <span class="text-sm text-zinc-600">
                    {{ __('attendance.statuses.present') }} {{ $row['counts']['present'] }} · {{ __('attendance.statuses.late') }} {{ $row['counts']['late'] }} ·
                    {{ __('attendance.statuses.absent') }} {{ $row['counts']['absent'] }} · {{ __('attendance.statuses.excused') }} {{ $row['counts']['excused'] }}
                    @if ($row['counts']['rate'] !== null) · {{ __('attendance.fields.rate') }} {{ $row['counts']['rate'] }}% @endif
                </span>
            </summary>
            <table class="mt-3 w-full text-sm">
                <tbody>
                    @forelse ($row['meetings'] as $item)
                        <tr class="border-t border-zinc-100">
                            <td class="px-3 py-1">{{ $item['meeting']->meeting_date->isoFormat('ll (ddd)') }} {{ $item['meeting']->timeLabel() }}</td>
                            <td class="px-3 py-1">{{ $item['record']?->status->label() ?? __('attendance.portal.not_recorded') }}</td>
                            <td class="px-3 py-1 text-zinc-500">{{ $item['record']?->note }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-3 py-2 text-zinc-500">{{ __('attendance.portal.no_meetings') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </details>
    @empty
        <p class="text-sm text-zinc-500">{{ __('attendance.portal.no_courses') }}</p>
    @endforelse
</div>
