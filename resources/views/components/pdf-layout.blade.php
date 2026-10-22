<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? '' }}</title>
    <style>
        /* Fonts are installed in the Gotenberg image (Noto Sans CJK). Korean first for Hangul,
           Simplified Chinese for Han characters, Latin falls through to Noto Sans. */
        body { font-family: "Noto Sans CJK KR", "Noto Sans CJK SC", "Noto Sans", sans-serif; font-size: 11pt; color: #111; }
        :lang(zh) { font-family: "Noto Sans CJK SC", "Noto Sans CJK KR", "Noto Sans", sans-serif; }
        h1 { font-size: 18pt; margin: 0 0 12pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4pt 6pt; text-align: left; }
        @page { size: A4; }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
