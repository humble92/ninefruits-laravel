<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('evaluation.portal.title') }}</h1>
        <p class="text-sm text-zinc-600">{{ __('evaluation.portal.anonymous') }}</p>
    </div>

    @if ($messages !== [])
        <div role="status" @class([
            'rounded-lg border p-3 text-sm',
            'border-emerald-300 bg-emerald-50' => $messageTone === 'success',
            'border-red-300 bg-red-50' => $messageTone === 'error',
        ])>
            @foreach ($messages as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    @if ($selected && $form)
        <form wire:submit="submit" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium">{{ $selected->section->label() }} {{ $selected->version->title }}</h2>
                <button type="button" wire:click="$set('enrollmentId', null)" class="text-sm underline">{{ __('evaluation.portal.back') }}</button>
            </div>
            @if ($form->description)
                <p class="text-sm text-zinc-600">{{ $form->description }}</p>
            @endif
            <p class="text-xs text-zinc-500">1 = {{ $form->scale_low_label ?? '' }} · {{ $form->scale_points }} = {{ $form->scale_high_label ?? '' }}</p>

            @php($category = null)
            @foreach ($form->questions as $question)
                @if ($question->category && $question->category !== $category)
                    @php($category = $question->category)
                    <h3 class="border-b border-zinc-100 pt-2 text-sm font-medium">{{ $category }}</h3>
                @endif
                <fieldset class="text-sm" wire:key="question-{{ $question->id }}">
                    <legend class="mb-1">{{ $loop->iteration }}. {{ $question->text }}@if (! $question->is_required) <span class="text-zinc-500">({{ __('evaluation.portal.optional') }})</span>@endif</legend>
                    @if ($question->question_type === \App\Domain\Evaluation\Enums\QuestionType::Scale)
                        <div class="flex flex-wrap gap-3">
                            @for ($point = 1; $point <= $form->scale_points; $point++)
                                <label class="flex items-center gap-1">
                                    <input type="radio" wire:model="answers.{{ $question->id }}" value="{{ $point }}" name="question-{{ $question->id }}"> {{ $point }}
                                </label>
                            @endfor
                        </div>
                    @else
                        <textarea wire:model="answers.{{ $question->id }}" rows="3" maxlength="2000" class="w-full rounded border border-zinc-300 px-2 py-1" aria-label="{{ $question->text }}"></textarea>
                    @endif
                </fieldset>
            @endforeach

            <button type="submit" wire:loading.attr="disabled" wire:confirm="{{ __('evaluation.portal.submit_confirm') }}" class="rounded bg-zinc-900 px-4 py-2 font-medium text-white">{{ __('evaluation.portal.submit') }}</button>
        </form>
    @else
        <section class="rounded-lg border border-zinc-200 bg-white">
            @forelse ($open as $enrollment)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 text-sm last:border-0">
                    <span>{{ $enrollment->section->label() }} {{ $enrollment->version->title }} <span class="text-zinc-500">· {{ $enrollment->term->name }}</span></span>
                    @if (in_array($enrollment->id, $done, true))
                        <span class="text-emerald-700">{{ __('evaluation.portal.done') }}</span>
                    @else
                        <button type="button" wire:click="open({{ $enrollment->id }})" class="rounded bg-zinc-900 px-3 py-1.5 text-white">{{ __('evaluation.portal.evaluate') }}</button>
                    @endif
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-zinc-500">{{ __('evaluation.portal.none_open') }}</p>
            @endforelse
        </section>
    @endif
</div>
