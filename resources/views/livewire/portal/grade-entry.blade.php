<div class="space-y-6">
    <div>
        <a href="{{ route('portal.teaching', ['termId' => $section->term_id]) }}" class="text-sm underline">{{ __('offering.teaching.title') }}</a>
        <h1 class="text-2xl font-semibold">{{ __('grading.entry.title') }}: {{ $section->label() }} {{ $section->version->title }}</h1>
        <p class="text-sm text-zinc-600">
            {{ $section->term->name }} · {{ __('grading.entry.counts', ['none' => $counts['none'] ?? 0, 'draft' => $counts['draft'] ?? 0, 'submitted' => $counts['submitted'] ?? 0, 'finalized' => $counts['finalized'] ?? 0]) }}
        </p>
        <p class="text-sm {{ $windowOpen ? 'text-emerald-700' : 'text-zinc-500' }}">
            {{ $windowOpen ? __('grading.entry.window_open') : __('grading.entry.window_closed') }}
        </p>
    </div>

    <p class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm">
        <span class="font-medium">{{ $section->evaluation_method->label() }}</span> ·
        {{ $absolute
            ? __('grading.entry.absolute_help', ['scale' => $scale->name, 'comparison' => $comparisonScale?->name ?? '-'])
            : __('grading.entry.relative_help') }}
    </p>

    @if ($messages !== [])
        <div role="status" @class([
            'rounded-lg border p-3 text-sm',
            'border-emerald-300 bg-emerald-50' => $messageTone === 'success',
            'border-red-300 bg-red-50' => $messageTone === 'error',
            'border-zinc-200 bg-white' => $messageTone === 'info',
        ])>
            @foreach ($messages as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-3 py-2">{{ __('people.fields.student_number') }}</th>
                    <th class="px-3 py-2">{{ __('people.fields.name') }}</th>
                    <th class="px-3 py-2">{{ __('reference.resources.programs.label') }}</th>
                    @if ($absolute)
                        <th class="px-3 py-2">{{ __('grading.fields.score') }}</th>
                    @endif
                    <th class="px-3 py-2">{{ __('grading.fields.grade') }} ({{ $scale->code }})</th>
                    @if ($absolute && $comparisonScale)
                        <th class="px-3 py-2">{{ __('grading.fields.comparison_grade') }} ({{ $comparisonScale->code }})</th>
                    @endif
                    <th class="px-3 py-2">{{ __('registration.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $enrollment)
                    <tr class="border-t border-zinc-100 align-top" wire:key="enrollment-{{ $enrollment->id }}">
                        <td class="px-3 py-2">{{ $enrollment->studentRecord->student_number }}</td>
                        <td class="px-3 py-2">
                            {{ $enrollment->studentRecord->person->displayName() }}
                            @if ($enrollment->is_retake)
                                <span class="rounded bg-zinc-100 px-1 text-xs">{{ __('registration.portal.retake') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $enrollment->studentRecord->program->name }}</td>
                        @if ($editable($enrollment))
                            @if ($absolute)
                                <td class="px-3 py-2">
                                    @if ($takesScore($enrollment))
                                        <input type="number" min="0" max="100" step="0.01" wire:model.live.debounce.500ms="grades.{{ $enrollment->id }}.score"
                                            aria-label="{{ __('grading.fields.score') }} {{ $enrollment->studentRecord->student_number }}" class="w-20 rounded border border-zinc-300 px-2 py-1">
                                    @endif
                                </td>
                            @endif
                            <td class="px-3 py-2">
                                @if ($takesScore($enrollment))
                                    <span class="font-medium">{{ $previews[$enrollment->id]['grade'] ?? $enrollment->grade_code }}</span>
                                    @if ($options($enrollment)->isNotEmpty())
                                        <select wire:model="grades.{{ $enrollment->id }}.grade_code" aria-label="{{ __('grading.fields.grade') }} {{ $enrollment->studentRecord->student_number }}" class="ms-1 rounded border border-zinc-300 px-1 py-0.5 text-xs">
                                            <option value="">{{ __('grading.entry.by_score') }}</option>
                                            @foreach ($options($enrollment) as $code)
                                                <option value="{{ $code }}">{{ $code }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                @else
                                    <select wire:model="grades.{{ $enrollment->id }}.grade_code" aria-label="{{ __('grading.fields.grade') }} {{ $enrollment->studentRecord->student_number }}" class="rounded border border-zinc-300 px-2 py-1">
                                        <option value=""></option>
                                        @foreach ($options($enrollment) as $code)
                                            <option value="{{ $code }}">{{ $code }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            @if ($absolute && $comparisonScale)
                                <td class="px-3 py-2 text-zinc-600">{{ $previews[$enrollment->id]['comparison'] ?? $enrollment->comparison_grade_code }}</td>
                            @endif
                        @else
                            @if ($absolute)
                                <td class="px-3 py-2">{{ $enrollment->score === null ? '' : (float) $enrollment->score }}</td>
                            @endif
                            <td class="px-3 py-2 font-medium">{{ $enrollment->displayGrade() }}</td>
                            @if ($absolute && $comparisonScale)
                                <td class="px-3 py-2 text-zinc-600">{{ $enrollment->comparison_grade_code }}</td>
                            @endif
                        @endif
                        <td class="px-3 py-2">
                            {{ $enrollment->status === \App\Domain\Registration\Enums\EnrollmentStatus::Withdrawn ? $enrollment->status->label() : $enrollment->grade_status->label() }}
                            @foreach ($enrollment->gradeChangeRequests->where('status', $pendingStatus) as $request)
                                <span class="block text-xs text-amber-700">
                                    {{ __('grading.entry.change_pending', ['from' => $request->from_grade_code, 'to' => $request->to_grade_code]) }}
                                    @if ($request->requested_by_id === auth()->id())
                                        <button type="button" wire:click="cancelChange({{ $request->id }})" class="underline">{{ __('grading.actions.cancel_request') }}</button>
                                    @endif
                                </span>
                            @endforeach
                            @if ($enrollment->grade_status === \App\Domain\Grading\Enums\GradeStatus::Finalized && $enrollment->gradeChangeRequests->where('status', $pendingStatus)->isEmpty())
                                @if ($changeFor === $enrollment->id)
                                    <form wire:submit="requestChange" class="mt-2 space-y-2">
                                        @if ($takesScore($enrollment))
                                            <input type="number" min="0" max="100" step="0.01" wire:model="changeScore" placeholder="{{ __('grading.fields.new_score') }}" aria-label="{{ __('grading.fields.new_score') }}" class="w-28 rounded border border-zinc-300 px-2 py-1">
                                        @endif
                                        @if ($options($enrollment)->isNotEmpty())
                                            <select wire:model="changeGrade" aria-label="{{ __('grading.fields.new_grade') }}" class="rounded border border-zinc-300 px-2 py-1">
                                                <option value="">{{ $takesScore($enrollment) ? __('grading.entry.by_score') : __('grading.fields.new_grade') }}</option>
                                                @foreach ($options($enrollment) as $code)
                                                    <option value="{{ $code }}">{{ $code }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <textarea wire:model="changeReason" rows="2" placeholder="{{ __('common.reason') }}" aria-label="{{ __('common.reason') }}" class="block w-full rounded border border-zinc-300 px-2 py-1"></textarea>
                                        @error('changeGrade') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        @error('changeScore') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        @error('changeReason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        <button type="submit" class="rounded bg-zinc-900 px-3 py-1 text-white">{{ __('grading.actions.request_change') }}</button>
                                    </form>
                                @else
                                    <button type="button" wire:click="startChange({{ $enrollment->id }})" class="block text-xs underline">{{ __('grading.actions.request_change') }}</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-zinc-500">{{ __('grading.entry.no_students') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if ($canEnter)
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded border border-zinc-300 px-4 py-2">{{ __('grading.actions.save_draft') }}</button>
            <button type="button" wire:click="submit" wire:confirm="{{ __('grading.entry.submit_confirm') }}" wire:loading.attr="disabled" class="rounded bg-zinc-900 px-4 py-2 font-medium text-white">{{ __('grading.actions.submit') }}</button>
        </div>
        <p class="text-sm text-zinc-600">{{ __('grading.entry.help') }}</p>
    @endif
</div>
