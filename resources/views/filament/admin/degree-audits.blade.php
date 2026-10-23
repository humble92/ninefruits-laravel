<div class="space-y-6">
    @foreach ($results as $result)
        <div>
            <h3 class="mb-2 font-medium">{{ __('curriculum.program_roles.'.$result['role']) }} · {{ $result['program_code'] }}</h3>
            @include('filament.admin.degree-audit', ['result' => $result, 'auditedAt' => null])
        </div>
    @endforeach
</div>
