<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <header class="border-b border-zinc-200 bg-white">
        <nav class="mx-auto flex max-w-5xl flex-wrap items-center gap-4 px-4 py-3">
            <a href="{{ route('portal.dashboard') }}" class="font-semibold">{{ config('app.name') }}</a>
            <a href="{{ route('portal.dashboard') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.dashboard') }}</a>
            @php($portalUser = auth()->user())
            @php($personas = app(\App\Domain\Security\Services\PersonaResolver::class)->personasFor($portalUser))
            @if (in_array(\App\Domain\Security\Enums\Persona::Student, $personas, true) || in_array(\App\Domain\Security\Enums\Persona::Alumni, $personas, true))
                <a href="{{ route('portal.records') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.records') }}</a>
                <a href="{{ route('portal.grades') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.grades') }}</a>
                <a href="{{ route('portal.assessments') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.assessments') }}</a>
                <a href="{{ route('portal.certificates') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.certificates') }}</a>
                @if (in_array(\App\Domain\Security\Enums\Persona::Student, $personas, true))
                    <a href="{{ route('portal.registration') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.registration') }}</a>
                    <a href="{{ route('portal.evaluations') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.evaluations') }}</a>
                @endif
            @endif
            @if ($portalUser->person_id !== null && (in_array(\App\Domain\Security\Enums\Persona::Applicant, $personas, true) || \App\Models\Admission\AdmissionCycle::query()->acceptingApplications()->exists()))
                <a href="{{ route('portal.applications') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.applications') }}</a>
            @endif
            @if ($portalUser->person_id !== null)
                <a href="{{ route('portal.profile') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.profile') }}</a>
            @endif
            @if ($portalUser->person_id !== null)
                <a href="{{ route('portal.catalog') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.catalog') }}</a>
            @endif
            @if (in_array(\App\Domain\Security\Enums\Persona::Instructor, $personas, true))
                <a href="{{ route('portal.teaching') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.teaching') }}</a>
            @endif
            @if (in_array(\App\Domain\Security\Enums\Persona::Advisor, $personas, true))
                <a href="{{ route('portal.advisees') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.advisees') }}</a>
            @endif
            <a href="{{ route('portal.security') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.security') }}</a>
            @can(\App\Domain\Security\Enums\Permission::AdminPanelAccess->value)
                <a href="{{ url('/admin') }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('portal.navigation.admin') }}</a>
            @endcan
            <div class="ms-auto flex items-center gap-3">
                <x-locale-switcher />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('authentication.logout') }}</button>
                </form>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        <x-mfa-reminder />

        @if (session('status'))
            <p class="mb-4 rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
        @endif

        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
