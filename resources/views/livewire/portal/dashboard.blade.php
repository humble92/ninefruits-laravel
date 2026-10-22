<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('portal.dashboard.welcome', ['name' => $user->displayName()]) }}</h1>

    <section class="rounded-lg border border-zinc-200 bg-white p-6">
        <h2 class="mb-3 font-medium">{{ __('portal.dashboard.access') }}</h2>
        <dl class="grid gap-2 text-sm sm:grid-cols-[12rem_1fr]">
            <dt class="text-zinc-500">{{ __('portal.dashboard.roles') }}</dt>
            <dd>{{ $roles === [] ? __('common.none') : implode(', ', $roles) }}</dd>
            <dt class="text-zinc-500">{{ __('portal.dashboard.personas') }}</dt>
            <dd>{{ $personas === [] ? __('common.none') : collect($personas)->map->label()->implode(', ') }}</dd>
        </dl>
        @if ($canAccessAdmin)
            <a href="{{ url('/admin') }}" class="mt-4 inline-block text-sm font-medium underline">{{ __('portal.navigation.admin') }}</a>
        @endif
    </section>
</div>
