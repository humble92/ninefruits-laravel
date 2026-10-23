<x-pdf-layout :title="__('attendance.sheet.title')">
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-size: 8pt; }
        .head { display: flex; justify-content: space-between; margin-bottom: 6pt; }
        .head h1 { font-size: 14pt; margin: 0; }
        table.sheet th, table.sheet td { padding: 2pt 3pt; text-align: center; font-size: 7.5pt; }
        table.sheet th.name, table.sheet td.name { text-align: left; white-space: nowrap; }
        table.sheet th.date { writing-mode: vertical-rl; transform: rotate(180deg); height: 48pt; }
        .legend { margin-top: 6pt; color: #444; }
    </style>
    <div class="head">
        <div>
            <h1>{{ __('attendance.sheet.title') }}</h1>
            <div>{{ $institution }} · {{ $section->term->name }} · {{ $section->label() }} {{ $section->version->title }}</div>
            <div>{{ __('offering.fields.instructors') }}: {{ $section->instructors->map(fn ($i) => $i->person->displayName())->implode(', ') }}</div>
        </div>
        <div>{{ __('attendance.sheet.printed', ['date' => now()->isoFormat('LL')]) }}</div>
    </div>
    <table class="sheet">
        <thead>
            <tr>
                <th>#</th>
                <th class="name">{{ __('people.fields.student_number') }}</th>
                <th class="name">{{ __('people.fields.name') }}</th>
                @foreach ($meetings as $meeting)
                    <th class="date">{{ $meeting->meeting_date->format('m/d') }}</th>
                @endforeach
                <th>{{ __('attendance.sheet.present') }}</th>
                <th>{{ __('attendance.sheet.late') }}</th>
                <th>{{ __('attendance.sheet.absent') }}</th>
                <th>{{ __('attendance.sheet.excused') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($enrollments as $enrollment)
                @php($counts = $summary['students'][$enrollment->id])
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="name">{{ $enrollment->studentRecord->student_number }}</td>
                    <td class="name">{{ $enrollment->studentRecord->person->displayName() }}</td>
                    @foreach ($meetings as $meeting)
                        @php($status = $records->get($enrollment->id)?->get($meeting->id)?->status)
                        <td>{{ $status === null ? '' : __('attendance.sheet.marks.'.$status->value) }}</td>
                    @endforeach
                    <td>{{ $counts['present'] }}</td>
                    <td>{{ $counts['late'] }}</td>
                    <td>{{ $counts['absent'] }}</td>
                    <td>{{ $counts['excused'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="legend">{{ __('attendance.sheet.legend') }}</div>
</x-pdf-layout>
