<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('certificate.verify.title') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 text-zinc-900">
    <main class="mx-auto max-w-lg px-4 py-10">
        <h1 class="text-2xl font-semibold">{{ __('certificate.verify.title') }}</h1>
        <p class="mt-1 text-sm text-zinc-600">{{ __('certificate.verify.intro') }}</p>

        <form method="get" action="{{ route('certificates.verify') }}" class="mt-4 flex gap-2">
            <input type="text" name="code" value="{{ $code }}" maxlength="40" placeholder="{{ __('certificate.verify.code') }}" aria-label="{{ __('certificate.verify.code') }}" class="grow rounded border border-zinc-300 px-3 py-2">
            <button type="submit" class="rounded bg-zinc-900 px-4 py-2 font-medium text-white">{{ __('certificate.verify.check') }}</button>
        </form>

        @if ($result !== null)
            <section role="status" @class(['mt-6 rounded-lg border p-4', 'border-emerald-300 bg-emerald-50' => $result['valid'], 'border-red-300 bg-red-50' => ! $result['valid']])>
                <p class="font-medium">{{ $result['valid'] ? __('certificate.verify.valid') : __('certificate.verify.invalid_'.$result['reason']) }}</p>
                @if ($result['issue'])
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                        <dt class="text-zinc-500">{{ __('certificate.fields.type') }}</dt><dd>{{ $result['issue']->type->getTranslation('name', $result['issue']->locale) }}</dd>
                        <dt class="text-zinc-500">{{ __('certificate.fields.serial_number') }}</dt><dd>{{ $result['issue']->serial_number }}</dd>
                        <dt class="text-zinc-500">{{ __('certificate.fields.issued_at') }}</dt><dd>{{ $result['issue']->issued_at->timezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->isoFormat('LL') }}</dd>
                        <dt class="text-zinc-500">{{ __('certificate.verify.holder') }}</dt><dd>{{ $result['holder'] }} ({{ $result['student_number'] }})</dd>
                    </dl>
                @endif
            </section>
        @endif
    </main>
</body>
</html>
