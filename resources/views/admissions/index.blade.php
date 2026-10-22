<x-layouts.guest :title="__('admissions.public.title')">
    <h1 class="mb-2 text-xl font-semibold">{{ __('admissions.public.title') }}</h1>
    <p class="mb-6 text-sm text-zinc-600">{{ __('admissions.public.instructions') }}</p>

    @forelse ($cycles as $cycle)
        <section class="mb-4 border-b border-zinc-100 pb-4 last:border-0">
            <h2 class="font-medium">{{ $cycle->name }}</h2>
            <p class="text-sm text-zinc-600">
                {{ $cycle->isRolling() ? __('admissions.public.rolling') : __('admissions.public.closes', ['date' => $cycle->closes_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LLL')]) }}
            </p>
            @if ($cycle->description)
                <p class="mt-1 text-sm">{{ $cycle->description }}</p>
            @endif
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($cycle->programs as $offered)
                    <li>{{ $offered->program->name }}</li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-sm text-zinc-600">{{ __('admissions.public.none') }}</p>
    @endforelse

    <div class="mt-6 flex flex-col gap-2">
        @auth
            <a href="{{ route('portal.applications') }}" class="rounded bg-zinc-900 px-4 py-2 text-center text-sm font-medium text-white">{{ __('admissions.public.go_to_applications') }}</a>
        @else
            @if ($registrationEnabled && $cycles->isNotEmpty())
                <a href="{{ route('register') }}" class="rounded bg-zinc-900 px-4 py-2 text-center text-sm font-medium text-white">{{ __('admissions.public.register') }}</a>
            @endif
            <a href="{{ route('login') }}" class="rounded border border-zinc-300 px-4 py-2 text-center text-sm font-medium">{{ __('admissions.public.sign_in') }}</a>
        @endauth
    </div>
</x-layouts.guest>
