<div class="space-y-6">
    <div>
        <a href="{{ route('portal.sections.show', $section) }}" class="text-sm text-zinc-600 underline">{{ $section->label() }}</a>
        <h1 class="mt-1 text-2xl font-semibold">{{ __('offering.syllabus.edit') }}: {{ $section->label() }} {{ $section->version->title }}</h1>
        <p class="text-sm text-zinc-600">{{ $section->term->name }} · {{ $section->session->name }}</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-6 sm:grid-cols-2">
            <div>
                <label for="language" class="block text-sm font-medium">{{ __('offering.syllabus.language') }}</label>
                <input id="language" type="text" wire:model="language" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('language') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm sm:mt-6">
                <input type="checkbox" wire:model="published"> {{ __('offering.syllabus.publish') }}
            </label>
            @foreach (['overview', 'objectives', 'materials', 'policies'] as $part)
                <div class="sm:col-span-2">
                    <label for="{{ $part }}" class="block text-sm font-medium">{{ __('offering.syllabus.'.$part) }}</label>
                    <textarea id="{{ $part }}" wire:model="content.{{ $part }}" rows="4" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
                    @error($part) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <h2 class="mb-3 font-medium">{{ __('offering.syllabus.assessment') }}</h2>
            @foreach ($content['assessment'] as $index => $item)
                <div class="mb-2 flex gap-2" wire:key="assessment-{{ $index }}">
                    <input type="text" wire:model="content.assessment.{{ $index }}.item" placeholder="{{ __('offering.syllabus.assessment_item') }}" class="flex-1 rounded border border-zinc-300 px-3 py-2">
                    <input type="number" wire:model="content.assessment.{{ $index }}.weight" min="0" max="100" placeholder="%" aria-label="{{ __('offering.syllabus.weight') }}" class="w-24 rounded border border-zinc-300 px-3 py-2">
                    <button type="button" wire:click="removeAssessment({{ $index }})" class="text-sm text-red-700 underline">{{ __('portal.profile.remove') }}</button>
                </div>
            @endforeach
            <button type="button" wire:click="addAssessment" class="text-sm underline">{{ __('offering.syllabus.add_assessment') }}</button>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <h2 class="mb-3 font-medium">{{ __('offering.syllabus.weekly_plan') }}</h2>
            @foreach ($content['weekly_plan'] as $index => $week)
                <div class="mb-2 flex items-center gap-2" wire:key="week-{{ $index }}">
                    <span class="w-16 text-sm text-zinc-500">{{ __('offering.syllabus.week', ['week' => $week['week']]) }}</span>
                    <input type="text" wire:model="content.weekly_plan.{{ $index }}.topic" aria-label="{{ __('offering.syllabus.week', ['week' => $week['week']]) }}" class="flex-1 rounded border border-zinc-300 px-3 py-2">
                </div>
            @endforeach
        </section>

        <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('common.save') }}</button>
    </form>
</div>
