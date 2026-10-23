<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('assessment.portal.title') }}</h1>
        <p class="text-sm text-zinc-600">{{ __('assessment.portal.intro') }}</p>
    </div>

    @if ($message)
        <div role="status" @class(['rounded-lg border p-3 text-sm', 'border-emerald-300 bg-emerald-50' => $messageTone === 'success', 'border-red-300 bg-red-50' => $messageTone === 'error'])>{{ $message }}</div>
    @endif

    @if ($required->isNotEmpty())
        <section class="rounded-lg border border-zinc-200 bg-white p-4">
            <h2 class="font-medium">{{ __('assessment.portal.required') }}</h2>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($required as $row)
                    <li class="flex items-center gap-2">
                        <span @class(['rounded px-2 py-0.5 text-xs', 'bg-emerald-100 text-emerald-800' => $row['passed'], 'bg-zinc-100 text-zinc-700' => ! $row['passed']])>{{ $row['passed'] ? __('assessment.passed.yes') : __('assessment.portal.not_yet') }}</span>
                        {{ $row['type']->name }}
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <h2 class="font-medium">{{ __('assessment.portal.open_sessions') }}</h2>
        @forelse ($open as $row)
            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 pt-2 text-sm" wire:key="open-{{ $row['session']->id }}">
                <div>
                    <span class="font-medium">{{ $row['session']->type->name }}</span> · {{ $row['session']->held_date->isoFormat('LL') }}
                    @if ($row['session']->venue) · {{ $row['session']->venue }} @endif
                    <div class="text-xs text-zinc-500">{{ __('assessment.portal.closes', ['time' => $row['session']->registration_closes_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL')]) }}</div>
                </div>
                @if ($row['registration'])
                    <button type="button" wire:click="cancel({{ $row['registration']->id }})" wire:confirm="{{ __('assessment.portal.confirm_cancel') }}" class="rounded border border-zinc-300 px-3 py-1 text-sm">{{ __('assessment.portal.cancel') }}</button>
                @else
                    <button type="button" wire:click="register({{ $row['session']->id }})" class="rounded bg-zinc-900 px-3 py-1 text-sm text-white">{{ __('assessment.portal.register') }}</button>
                @endif
            </div>
        @empty
            <p class="mt-2 text-sm text-zinc-500">{{ __('assessment.portal.no_open_sessions') }}</p>
        @endforelse
    </section>

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <h2 class="font-medium">{{ __('assessment.portal.my_registrations') }}</h2>
        @forelse ($registrations as $registration)
            <div class="mt-2 border-t border-zinc-100 pt-2 text-sm" wire:key="registration-{{ $registration->id }}">
                <span class="font-medium">{{ $registration->session->type->name }}</span> · {{ $registration->session->held_date->isoFormat('LL') }}
                <span class="text-zinc-500">· {{ $registration->status->label() }}</span>
            </div>
        @empty
            <p class="mt-2 text-sm text-zinc-500">{{ __('assessment.portal.no_registrations') }}</p>
        @endforelse
    </section>

    <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <h2 class="px-4 pt-4 font-medium">{{ __('assessment.portal.my_results') }}</h2>
        <table class="mt-2 w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('assessment.fields.taken_date') }}</th>
                    <th class="px-4 py-2">{{ __('assessment.fields.type') }}</th>
                    <th class="px-4 py-2">{{ __('assessment.fields.total_score') }}</th>
                    <th class="px-4 py-2">{{ __('assessment.fields.level') }}</th>
                    <th class="px-4 py-2">{{ __('assessment.fields.is_passed') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($results as $result)
                    <tr class="border-t border-zinc-100" wire:key="result-{{ $result->id }}">
                        <td class="px-4 py-2">{{ $result->taken_date->isoFormat('LL') }}</td>
                        <td class="px-4 py-2">{{ $result->type->name }} <span class="text-zinc-500">({{ $result->source->label() }})</span></td>
                        <td class="px-4 py-2">{{ $result->total_score ?? '-' }}
                            @if ($result->componentScores->isNotEmpty())<div class="text-xs text-zinc-500">{{ $result->componentScores->map(fn ($s) => $s->component->name.' '.$s->score)->implode(' · ') }}</div>@endif
                        </td>
                        <td class="px-4 py-2">{{ $result->level ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $result->is_passed ? __('assessment.passed.yes') : __('assessment.passed.no') }}</td>
                        <td class="px-4 py-2">@if ($result->getMedia(\App\Models\Assessment\AssessmentResult::EVIDENCE_COLLECTION)->isNotEmpty())<a href="{{ route('assessments.evidence', $result) }}" target="_blank" class="underline">{{ __('assessment.fields.evidence') }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">{{ __('assessment.portal.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
