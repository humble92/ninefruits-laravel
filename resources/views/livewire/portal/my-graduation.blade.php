<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('graduation.portal.title') }}</h1>
        <p class="text-sm text-zinc-600">{{ __('graduation.portal.intro', ['year' => $record->catalog_year]) }}</p>
    </div>
    @foreach ($results as $result)
        <section class="rounded-lg border border-zinc-200 bg-white p-4">
            <h2 class="font-medium">{{ __('curriculum.program_roles.'.$result['role']) }} · {{ $result['program_code'] }}</h2>
            @include('filament.admin.degree-audit', ['result' => $result, 'auditedAt' => null])
        </section>
    @endforeach
</div>
