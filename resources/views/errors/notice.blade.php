{{--
    Shared browser notice for error pages and controller notices.
    Self-contained (inline CSS and SVG, no Vite build, no database for 5xx) so it renders even
    when the cause is the database or the build. Never print exception messages here: only the
    fixed, translated title and message, the status code and the request id.
--}}
@php
    $code ??= null;
    $icon ??= match (true) {
        $code === 403 => 'lock',
        $code === 404 => 'search',
        $code === 419 => 'refresh',
        $code === 429 => 'clock',
        $code === 503 => 'pause',
        default => 'warning',
    };
    $reference = \Illuminate\Support\Facades\Context::get('request_id');

    if (! isset($linkUrl)) {
        // 5xx pages must not touch the session or the database.
        $user = $code !== null && $code >= 500 ? null : rescue(fn () => auth()->user(), report: false);
        $toAdmin = $user !== null && request()->is('admin', 'admin/*')
            && rescue(fn () => $user->can(\App\Domain\Security\Enums\Permission::AdminPanelAccess->value), false, false);

        [$linkUrl, $linkLabel] = $toAdmin
            ? [url('/admin'), __('portal.navigation.admin')]
            : [$user !== null ? route('portal.dashboard') : url('/'), __('portal.navigation.dashboard')];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; background: #fafafa; color: #18181b; font: 1rem/1.6 system-ui, sans-serif; }
        main { max-width: 32rem; margin: clamp(2rem, 12vh, 8rem) auto; padding: 0 1rem; }
        section { padding: 1.5rem; border: 1px solid #e4e4e7; border-radius: .75rem; background: white; }
        .icon { display: inline-flex; width: 2.75rem; height: 2.75rem; margin-bottom: .75rem; align-items: center; justify-content: center; border-radius: 9999px; background: #eef2ff; color: #3730a3; }
        .icon svg { width: 1.5rem; height: 1.5rem; }
        h1 { margin: 0; font-size: 1.5rem; line-height: 1.3; }
        p { margin: .75rem 0 1.25rem; color: #52525b; }
        a { color: #3730a3; text-underline-offset: .2em; }
        .meta { margin: 1.25rem 0 0; padding-top: .75rem; border-top: 1px solid #f4f4f5; font-size: .8125rem; color: #71717a; }
        .meta code { font: .8125rem ui-monospace, monospace; user-select: all; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <main>
        <section>
            <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    @switch($icon)
                        @case('lock')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            @break
                        @case('search')
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            @break
                        @case('refresh')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            @break
                        @case('clock')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            @break
                        @case('pause')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            @break
                        @default
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    @endswitch
                </svg>
            </span>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ $linkUrl }}">{{ $linkLabel }}</a>
            @if ($code !== null || $reference !== null)
                <p class="meta">
                    @if ($code !== null){{ __('errors.code', ['code' => $code]) }}@endif
                    @if ($code !== null && $reference !== null) · @endif
                    @if ($reference !== null){{ __('errors.reference') }} <code>{{ $reference }}</code>@endif
                </p>
            @endif
        </section>
    </main>
</body>
</html>
