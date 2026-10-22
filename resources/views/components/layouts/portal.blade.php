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
