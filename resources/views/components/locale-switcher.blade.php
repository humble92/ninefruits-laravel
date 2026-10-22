@php($locales = app(\App\Domain\Institution\Services\SupportedLocales::class))
<form method="POST" action="{{ route('locale.update') }}" class="text-sm">
    @csrf
    <label for="locale-switcher" class="sr-only">{{ __('common.language') }}</label>
    <select id="locale-switcher" name="locale" onchange="this.form.submit()" class="rounded border border-zinc-300 bg-white px-2 py-1">
        @foreach ($locales->all() as $locale)
            <option value="{{ $locale }}" @selected($locale === app()->getLocale())>{{ $locales->label($locale) }}</option>
        @endforeach
    </select>
    <noscript><button type="submit">{{ __('common.apply') }}</button></noscript>
</form>
