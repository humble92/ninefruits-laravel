<div class="space-y-6">
    <div>
        <a href="{{ route('portal.teaching', ['termId' => $section->term_id]) }}" class="text-sm underline">{{ __('offering.teaching.title') }}</a>
        <h1 class="text-2xl font-semibold">{{ __('evaluation.results.title') }}: {{ $section->label() }} {{ $section->version->title }}</h1>
        <p class="text-sm text-zinc-600">
            {{ $section->term->name }} · {{ __('evaluation.results.responses', ['responses' => $result->responses, 'expected' => $result->expected, 'rate' => $result->ratePercent]) }}
            @if ($result->overallMean !== null)
                · {{ __('evaluation.results.overall', ['mean' => number_format($result->overallMean, 2)]) }}
            @endif
        </p>
    </div>

    @if (! $result->shown)
        <p role="status" class="rounded-lg border border-zinc-200 bg-white p-3 text-sm">
            @if ($result->termEvaluation?->results_published_at === null)
                {{ __('evaluation.results.not_published') }}
            @else
                {{ __('evaluation.results.too_few', ['minimum' => $result->termEvaluation->minimum_responses, 'percent' => $result->termEvaluation->minimum_response_percent]) }}
            @endif
        </p>
    @else
        @php($form = $result->termEvaluation->form)
        <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left">
                    <tr>
                        <th class="px-3 py-2">{{ __('evaluation.fields.question') }}</th>
                        <th class="px-3 py-2">{{ __('evaluation.results.mean') }}</th>
                        @for ($point = 1; $point <= $form->scale_points; $point++)
                            <th class="px-3 py-2 text-center">{{ $point }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach ($form->questions as $question)
                        @if (isset($result->questions[$question->id]))
                            @php($row = $result->questions[$question->id])
                            <tr class="border-t border-zinc-100">
                                <td class="px-3 py-2">{{ $loop->iteration }}. {{ $question->text }}@if ($question->is_reversed) <span class="text-xs text-zinc-500">({{ __('evaluation.results.reversed') }})</span>@endif</td>
                                <td class="px-3 py-2 font-medium">{{ $row['mean'] === null ? '-' : number_format($row['mean'], 2) }}</td>
                                @foreach ($row['distribution'] as $count)
                                    <td class="px-3 py-2 text-center text-zinc-600">{{ $count }}</td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </section>

        @foreach ($form->questions as $question)
            @if (isset($result->comments[$question->id]))
                <section class="rounded-lg border border-zinc-200 bg-white">
                    <h2 class="border-b border-zinc-100 px-4 py-3 font-medium">{{ $question->text }}</h2>
                    @forelse ($result->comments[$question->id] as $comment)
                        <p class="whitespace-pre-line border-b border-zinc-100 px-4 py-2 text-sm last:border-0">{{ $comment }}</p>
                    @empty
                        <p class="px-4 py-3 text-sm text-zinc-500">{{ __('common.none') }}</p>
                    @endforelse
                </section>
            @endif
        @endforeach
    @endif
</div>
