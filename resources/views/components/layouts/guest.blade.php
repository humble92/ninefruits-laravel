<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12">
        <div class="mb-6 flex items-center justify-between">
            <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            <x-locale-switcher />
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            @if (session('status'))
                <p class="mb-4 rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif

            {{ $slot }}
        </div>
    </main>
</body>
</html>
