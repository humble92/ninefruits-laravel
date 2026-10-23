<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('offering.teaching.title') }}</h1>
        @if ($terms->isNotEmpty())
            <select wire:model.live="termId" aria-label="{{ __('reference.resources.terms.label') }}" class="rounded border border-zinc-300 px-3 py-2">
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <x-timetable :timetable="$timetable" />
    </section>

    <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('offering.fields.section') }}</th>
                    <th class="px-4 py-2">{{ __('curriculum.fields.title') }}</th>
                    <th class="px-4 py-2">{{ __('offering.fields.schedule') }}</th>
                    <th class="px-4 py-2">{{ __('offering.syllabus.title') }}</th>
                    <th class="px-4 py-2">{{ __('grading.entry.title') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sections as $section)
                    <tr class="border-t border-zinc-100">
                        <td class="px-4 py-2"><a href="{{ route('portal.sections.show', $section) }}" class="underline">{{ $section->label() }}</a></td>
                        <td class="px-4 py-2">{{ $section->version->title }}</td>
                        <td class="px-4 py-2">{{ \App\Domain\Offering\Services\Timetable::summary($section->meetings) ?: '-' }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('portal.syllabus.edit', $section) }}" class="underline">
                                {{ $section->syllabus?->published_at ? __('offering.syllabus.published') : ($section->syllabus ? __('offering.syllabus.draft') : __('offering.syllabus.write')) }}
                            </a>
                        </td>
                        <td class="px-4 py-2"><a href="{{ route('portal.grades.entry', $section) }}" class="underline">{{ __('grading.entry.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">{{ __('offering.teaching.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
