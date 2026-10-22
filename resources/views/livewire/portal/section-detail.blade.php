<div class="space-y-6">
    <div>
        <a href="{{ route('portal.catalog', ['termId' => $section->term_id]) }}" class="text-sm text-zinc-600 underline">{{ __('offering.catalog.title') }}</a>
        <h1 class="mt-1 text-2xl font-semibold">{{ $section->label() }} {{ $section->version->title }}</h1>
        <p class="text-sm text-zinc-600">{{ $section->term->name }} · {{ $section->session->name }} ({{ $section->session->start_date->toDateString() }} ~ {{ $section->session->end_date->toDateString() }})</p>
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <dl class="grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
            <dt class="text-zinc-500">{{ __('curriculum.fields.credits') }}</dt>
            <dd>{{ $section->version->credits }} ({{ __('offering.syllabus.hours', ['lecture' => $section->version->lecture_hours, 'lab' => $section->version->lab_hours]) }})</dd>
            <dt class="text-zinc-500">{{ __('curriculum.fields.category') }}</dt><dd>{{ $section->version->category?->name ?? '-' }}</dd>
            <dt class="text-zinc-500">{{ __('curriculum.fields.grading_basis') }}</dt><dd>{{ $section->version->grading_basis->label() }}</dd>
            <dt class="text-zinc-500">{{ __('offering.fields.instructors') }}</dt>
            <dd>{{ $section->instructors->map(fn ($instructor) => $instructor->person->displayName())->implode(', ') ?: __('offering.catalog.instructor_tba') }}</dd>
            <dt class="text-zinc-500">{{ __('offering.fields.schedule') }}</dt>
            <dd>{{ \App\Domain\Offering\Services\Timetable::summary($section->meetings) ?: __('offering.catalog.time_tba') }}</dd>
            <dt class="text-zinc-500">{{ __('offering.fields.capacity') }}</dt><dd>{{ $section->capacity }}</dd>
            @if ($section->instruction_language)
                <dt class="text-zinc-500">{{ __('offering.fields.instruction_language') }}</dt><dd>{{ $section->instruction_language }}</dd>
            @endif
            @if ($section->version->requisites->isNotEmpty())
                <dt class="text-zinc-500">{{ __('curriculum.fields.requisites') }}</dt>
                <dd>
                    @foreach ($section->version->requisites->groupBy(fn ($requisite) => $requisite->kind->value.'-'.$requisite->group_number) as $group)
                        <span class="block">{{ $group->first()->kind->label() }}: {{ $group->map(fn ($requisite) => $requisite->requiredCourse->code)->implode(' '.__('curriculum.or').' ') }}</span>
                    @endforeach
                </dd>
            @endif
            @if ($section->version->description)
                <dt class="text-zinc-500">{{ __('curriculum.fields.description') }}</dt><dd class="whitespace-pre-line">{{ $section->version->description }}</dd>
            @endif
        </dl>
    </section>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-medium">{{ __('offering.syllabus.title') }}</h2>
            @if ($canEdit)
                <a href="{{ route('portal.syllabus.edit', $section) }}" class="text-sm underline">{{ __('offering.syllabus.edit') }}</a>
            @endif
        </div>
        @if ($syllabus === null)
            <p class="text-sm text-zinc-500">{{ __('offering.syllabus.none') }}</p>
        @else
            @if ($syllabus->published_at === null)
                <p class="mb-3 rounded bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ __('offering.syllabus.draft_notice') }}</p>
            @endif
            <div class="space-y-4 text-sm">
                @foreach (['overview', 'objectives', 'materials', 'policies'] as $part)
                    @if (filled($syllabus->content[$part] ?? null))
                        <div>
                            <h3 class="font-medium">{{ __('offering.syllabus.'.$part) }}</h3>
                            <p class="whitespace-pre-line text-zinc-700">{{ $syllabus->content[$part] }}</p>
                        </div>
                    @endif
                @endforeach
                @if (($syllabus->content['assessment'] ?? []) !== [])
                    <div>
                        <h3 class="font-medium">{{ __('offering.syllabus.assessment') }}</h3>
                        <ul class="list-inside list-disc text-zinc-700">
                            @foreach ($syllabus->content['assessment'] as $item)
                                <li>{{ $item['item'] }}@if (($item['weight'] ?? null) !== null) {{ $item['weight'] }}%@endif</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (($syllabus->content['weekly_plan'] ?? []) !== [])
                    <div>
                        <h3 class="font-medium">{{ __('offering.syllabus.weekly_plan') }}</h3>
                        <table class="mt-1 w-full">
                            @foreach ($syllabus->content['weekly_plan'] as $week)
                                <tr class="border-t border-zinc-100"><td class="w-20 py-1 text-zinc-500">{{ __('offering.syllabus.week', ['week' => $week['week']]) }}</td><td class="py-1">{{ $week['topic'] }}</td></tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
