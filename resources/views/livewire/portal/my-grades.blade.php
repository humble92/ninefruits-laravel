<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('grading.grades.title') }}</h1>
            <p class="text-sm text-zinc-600">{{ $record->student_number }} · {{ $record->program->name }}</p>
        </div>
        @if ($hold === null)
            <a href="{{ route('portal.grades.transcript') }}" target="_blank" class="rounded border border-zinc-300 px-3 py-2 text-sm">{{ __('grading.transcript.print') }}</a>
        @endif
    </div>

    @if ($hold !== null)
        <p role="status" class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm">{{ __('grading.grades.blocked', ['type' => $hold->hold_type->label()]) }}</p>
    @else
        @if ($latest)
            <section class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 text-sm sm:grid-cols-4">
                <p><span class="text-zinc-500">{{ __('grading.fields.cumulative_gpa') }}</span><br><span class="text-lg font-semibold">{{ $latest->cumulative_gpa ?? '-' }}</span></p>
                <p><span class="text-zinc-500">{{ __('grading.fields.earned_credits') }}</span><br>{{ (float) $latest->cumulative_earned_credits }}</p>
                <p><span class="text-zinc-500">{{ __('grading.fields.attempted_credits') }}</span><br>{{ (float) $latest->cumulative_attempted_credits }}</p>
                <p><span class="text-zinc-500">{{ __('grading.fields.standing') }}</span><br>{{ $latest->academic_standing?->label() ?? '-' }}</p>
            </section>
        @endif

        @forelse ($terms->reverse() as $term)
            <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3">
                    <h2 class="font-medium">{{ $term['term']->name }}</h2>
                    @if ($term['record'])
                        <p class="text-sm text-zinc-600">
                            {{ __('grading.grades.term_summary', ['gpa' => $term['record']->term_gpa ?? '-', 'earned' => (float) $term['record']->earned_credits, 'attempted' => (float) $term['record']->attempted_credits]) }}
                            @if ($term['record']->academic_standing && $term['record']->academic_standing !== \App\Domain\Grading\Enums\AcademicStanding::Good)
                                · <span class="text-red-700">{{ $term['record']->academic_standing->label() }}</span>
                            @endif
                            @if ($term['record']->is_honors)
                                · <span class="text-emerald-700">{{ __('grading.fields.honors') }}</span>
                            @endif
                        </p>
                    @endif
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-zinc-500">
                        <tr>
                            <th class="px-4 py-2">{{ __('curriculum.fields.code') }}</th>
                            <th class="px-4 py-2">{{ __('curriculum.fields.title') }}</th>
                            <th class="px-4 py-2">{{ __('curriculum.fields.credits') }}</th>
                            <th class="px-4 py-2">{{ __('grading.fields.grade') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($term['enrollments'] as $enrollment)
                            <tr class="border-t border-zinc-100">
                                <td class="px-4 py-2">{{ $enrollment->course->code }}</td>
                                <td class="px-4 py-2">
                                    {{ $enrollment->version->title }}
                                    @if ($enrollment->is_retake)
                                        <span class="rounded bg-zinc-100 px-1 text-xs">{{ __('registration.portal.retake') }}</span>
                                    @endif
                                    @if (isset($superseded[$enrollment->id]))
                                        <span class="rounded bg-zinc-100 px-1 text-xs">{{ __('grading.grades.superseded') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ (float) $enrollment->credits }}</td>
                                <td class="px-4 py-2 font-medium">{{ $enrollment->displayGrade() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <p class="text-sm text-zinc-600">{{ __('grading.grades.none') }}</p>
        @endforelse
    @endif
</div>
