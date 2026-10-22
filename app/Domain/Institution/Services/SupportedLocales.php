<?php

namespace App\Domain\Institution\Services;

use App\Domain\Institution\Settings\InstitutionSettings;
use Illuminate\Http\Request;
use Locale;

/**
 * Locales offered by this installation: the institution's supported locales that have a
 * lang/{locale} directory. Adding a language = adding the directory + registering it in
 * the institution settings. Nothing in code lists languages.
 */
class SupportedLocales
{
    public function __construct(private readonly InstitutionSettings $settings) {}

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $fallback = (string) config('app.fallback_locale');
        $locales = array_filter(
            $this->settings->supported_locales,
            fn (string $locale) => is_dir(lang_path($locale)),
        );

        return array_values(array_unique([...$locales, $fallback]));
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->all(), true);
    }

    public function default(): string
    {
        return $this->isSupported($this->settings->default_locale)
            ? $this->settings->default_locale
            : (string) config('app.fallback_locale');
    }

    public function negotiate(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            // Browsers send zh-CN or zh_CN; lang directories use zh_CN.
            $candidate = str_replace('-', '_', $language);

            foreach ([$candidate, strtok($candidate, '_')] as $option) {
                foreach ($this->all() as $locale) {
                    if (strcasecmp($locale, (string) $option) === 0) {
                        return $locale;
                    }
                }
            }
        }

        return null;
    }

    /**
     * The language name written in that language, e.g. "한국어", "中文 (中国)".
     */
    public function label(string $locale): string
    {
        return Locale::getDisplayName($locale, $locale) ?: $locale;
    }
}
