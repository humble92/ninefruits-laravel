<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('admissions.portal.title') }}</h1>

    <section class="rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('admissions.fields.cycle') }}</th>
                    <th class="px-4 py-2">{{ __('reference.fields.programs') }}</th>
                    <th class="px-4 py-2">{{ __('admissions.fields.application_number') }}</th>
                    <th class="px-4 py-2">{{ __('admissions.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr class="border-t border-zinc-100">
                        <td class="px-4 py-2"><a href="{{ route('portal.applications.show', $application) }}" class="underline">{{ $application->cycle->name }}</a></td>
                        <td class="px-4 py-2">{{ $application->program->name }}</td>
                        <td class="px-4 py-2">{{ $application->application_number ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $application->applicantStatus()->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-500">{{ __('admissions.portal.no_applications') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <h2 class="mb-4 font-medium">{{ __('admissions.portal.new_application') }}</h2>
        @if ($cycles->isEmpty())
            <p class="text-sm text-zinc-600">{{ __('admissions.public.none') }}</p>
        @else
            <form wire:submit="start" class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="cycle" class="block text-sm font-medium">{{ __('admissions.fields.cycle') }}</label>
                    <select id="cycle" wire:model.live="cycleId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                        <option value="">-</option>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                        @endforeach
                    </select>
                    @error('cycleId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('admission_cycle_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="program" class="block text-sm font-medium">{{ __('reference.fields.programs') }}</label>
                    <select id="program" wire:model="programId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                        <option value="">-</option>
                        @foreach ($programs as $offered)
                            <option value="{{ $offered->program_id }}">{{ $offered->program->name }}</option>
                        @endforeach
                    </select>
                    @error('programId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('program_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('admissions.portal.start') }}</button>
                </div>
            </form>
        @endif
    </section>
</div>
