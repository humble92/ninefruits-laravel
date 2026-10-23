<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('certificate.portal.title') }}</h1>
        <p class="text-sm text-zinc-600">{{ __('certificate.portal.intro') }}</p>
    </div>
    <section class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('certificate.fields.issued_at') }}</th>
                    <th class="px-4 py-2">{{ __('certificate.fields.type') }}</th>
                    <th class="px-4 py-2">{{ __('certificate.fields.serial_number') }}</th>
                    <th class="px-4 py-2">{{ __('registration.fields.status') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    <tr class="border-t border-zinc-100">
                        <td class="px-4 py-2">{{ $issue->issued_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LL') }}</td>
                        <td class="px-4 py-2">{{ $issue->type->getTranslation('name', $issue->locale) }} <span class="text-zinc-500">({{ $issue->locale }})</span></td>
                        <td class="px-4 py-2">{{ $issue->serial_number }}</td>
                        <td class="px-4 py-2">{{ $issue->status->label() }}</td>
                        <td class="px-4 py-2"><a href="{{ route('certificates.download', $issue) }}" target="_blank" class="underline">{{ __('certificate.actions.download') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">{{ __('certificate.portal.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
