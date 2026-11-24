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
        h1 { margin: 0; font-size: 1.5rem; line-height: 1.3; }
        p { margin: .75rem 0 1.25rem; color: #52525b; }
        a { color: #3730a3; text-underline-offset: .2em; }
    </style>
</head>
<body>
    <main>
        <section>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ $linkUrl ?? url('/') }}">{{ $linkLabel ?? __('portal.navigation.dashboard') }}</a>
        </section>
    </main>
</body>
</html>
