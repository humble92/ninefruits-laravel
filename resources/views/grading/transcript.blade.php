<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('grading.transcript.title') }} {{ $record->student_number }}</title>
    <style>
        body { font-family: system-ui, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border-bottom: 1px solid #ddd; padding: 3px 6px; text-align: left; }
        th { background: #f4f4f5; }
        .term { break-inside: avoid; }
        .muted { color: #555; }
        .note { font-size: 11px; color: #555; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">{{ __('grading.transcript.print') }}</button></p>
    <h1>{{ $institution }} · {{ __('grading.transcript.title') }}</h1>
    <p class="muted">{{ __('grading.transcript.unofficial') }}</p>
    <table>
        <tr>
            <th>{{ __('people.fields.student_number') }}</th><td>{{ $record->student_number }}</td>
            <th>{{ __('people.fields.name') }}</th><td>{{ $record->person->displayName() }}</td>
        </tr>
        <tr>
            <th>{{ __('reference.resources.programs.label') }}</th><td>{{ $record->program->name }}</td>
            <th>{{ __('grading.fields.cumulative_gpa') }}</th>
            <td>{{ $latest?->cumulative_gpa ?? '-' }} ({{ __('grading.fields.earned_credits') }} {{ $latest ? (float) $latest->cumulative_earned_credits : 0 }})</td>
        </tr>
    </table>

    @foreach ($terms as $term)
        <div class="term">
            <h2 style="font-size: 14px; margin: 12px 0 4px;">{{ $term['term']->name }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('curriculum.fields.code') }}</th>
                        <th>{{ __('curriculum.fields.title') }}</th>
                        <th>{{ __('curriculum.fields.credits') }}</th>
                        <th>{{ __('grading.fields.grade') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($term['enrollments'] as $enrollment)
                        <tr>
                            <td>{{ $enrollment->course->code }}</td>
                            <td>{{ $enrollment->version->title }}@if ($enrollment->is_retake) ({{ __('registration.portal.retake') }})@endif @if (isset($superseded[$enrollment->id])) *@endif</td>
                            <td>{{ (float) $enrollment->credits }}</td>
                            <td>{{ $enrollment->displayGrade() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($term['record'])
                <p class="muted">{{ __('grading.grades.term_summary', ['gpa' => $term['record']->term_gpa ?? '-', 'earned' => (float) $term['record']->earned_credits, 'attempted' => (float) $term['record']->attempted_credits]) }}
                    · {{ __('grading.fields.cumulative_gpa') }} {{ $term['record']->cumulative_gpa ?? '-' }}</p>
            @endif
        </div>
    @endforeach

    <p class="note">* {{ __('grading.grades.superseded') }}</p>
    <p class="note">{{ __('grading.transcript.printed_at', ['date' => now()->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL')]) }}</p>
</body>
</html>
