@php($satisfied = $result['satisfied'] ?? false)
<div class="space-y-4 text-sm">
    <div @class(['rounded-lg border p-3', 'border-success-300 bg-success-50 dark:bg-success-500/10' => $satisfied, 'border-danger-300 bg-danger-50 dark:bg-danger-500/10' => ! $satisfied])>
        <div class="font-medium">{{ $satisfied ? __('graduation.satisfied.yes') : __('graduation.satisfied.no') }} · {{ __('graduation.fields.earned_credits') }} {{ $result['earned_credits'] }}@if ($result['required_credits'] !== null) / {{ $result['required_credits'] }}@endif · {{ __('grading.fields.cumulative_gpa') }} {{ $result['cumulative_gpa'] ?? '-' }}@if ($result['minimum_gpa'] !== null) (≥ {{ $result['minimum_gpa'] }})@endif</div>
        @if (! empty($result['issues']))
            <ul class="mt-1 list-inside list-disc">
                @foreach ($result['issues'] as $issue)<li>{{ $issue }}</li>@endforeach
            </ul>
        @endif
        @if ($auditedAt)<div class="mt-1 text-xs text-gray-500">{{ __('graduation.fields.audited_at') }}: {{ $auditedAt->isoFormat('LLL') }}</div>@endif
    </div>
    @foreach ($result['requirements'] ?? [] as $requirement)
        <details class="rounded-lg border border-gray-200 p-3 dark:border-white/10" @if (! $requirement['satisfied']) open @endif>
            <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2">
                <span>
                    <span @class(['mr-2 rounded px-1.5 py-0.5 text-xs', 'bg-success-100 text-success-800' => $requirement['satisfied'], 'bg-danger-100 text-danger-800' => ! $requirement['satisfied']])>{{ $requirement['waived'] ? __('graduation.fields.waived') : ($requirement['satisfied'] ? __('graduation.satisfied.yes') : __('graduation.satisfied.no')) }}</span>
                    {{ $requirement['name'] }}
                </span>
                <span class="text-gray-500">
                    @if ($requirement['required_credits'] !== null) {{ $requirement['achieved_credits'] }} / {{ $requirement['required_credits'] }} {{ __('curriculum.fields.credits') }} @endif
                    @if ($requirement['required_courses'] !== null) · {{ $requirement['achieved_courses'] }} / {{ $requirement['required_courses'] }} {{ __('graduation.fields.courses') }} @endif
                </span>
            </summary>
            @if (! empty($requirement['missing']))
                <p class="mt-2 text-danger-700">{{ __('graduation.fields.missing') }}: {{ implode(', ', $requirement['missing']) }}</p>
            @endif
            @if (! empty($requirement['courses']))
                <table class="mt-2 w-full text-xs">
                    @foreach ($requirement['courses'] as $course)
                        <tr><td class="pr-3">{{ $course['code'] }}</td><td class="pr-3">{{ $course['title'] }}</td><td class="pr-3">{{ $course['credits'] }}</td><td>{{ $course['grade'] }}</td></tr>
                    @endforeach
                </table>
            @endif
        </details>
    @endforeach
</div>
