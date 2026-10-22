<?php

namespace App\Http\Middleware;

use App\Domain\Institution\Services\SupportedLocales;
use App\Support\Database\DatabaseSessionContext;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale order: user preference, session choice, browser language, institution default.
 */
class SetLocale
{
    public const string SESSION_KEY = 'locale';

    public function __construct(
        private readonly SupportedLocales $locales,
        private readonly DatabaseSessionContext $databaseContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = collect([
            $request->user()?->locale,
            $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null,
            $this->locales->negotiate($request),
        ])->first(fn (?string $candidate) => $this->locales->isSupported($candidate)) ?? $this->locales->default();

        App::setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        Number::useLocale($locale);

        $this->databaseContext->setLocale($locale, (string) config('app.fallback_locale'));

        return $next($request);
    }
}
