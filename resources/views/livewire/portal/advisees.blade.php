<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('portal.advisees.title') }}</h1>

    <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('people.fields.student_number') }}</th>
                    <th class="px-4 py-2">{{ __('people.fields.name') }}</th>
                    <th class="px-4 py-2">{{ __('reference.fields.programs') }}</th>
                    <th class="px-4 py-2">{{ __('people.fields.year_level') }}</th>
                    <th class="px-4 py-2">{{ __('people.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr class="border-t border-zinc-100">
                        <td class="px-4 py-2">{{ $assignment->studentRecord->student_number }}</td>
                        <td class="px-4 py-2">{{ $assignment->studentRecord->person->displayName() }}</td>
                        <td class="px-4 py-2">{{ $assignment->studentRecord->program->name }}</td>
                        <td class="px-4 py-2">{{ $assignment->studentRecord->year_level }}</td>
                        <td class="px-4 py-2">{{ $assignment->studentRecord->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">{{ __('common.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
