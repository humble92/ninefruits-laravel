<?php

namespace Database\Seeders\Essential;

use App\Models\Institution\Language;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Illuminate\Database\Seeder;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

/**
 * ISO 639-1 languages (two-letter codes) with names in every installed language.
 */
class LanguageSeeder extends Seeder
{
    use SeedsTranslatedRows;

    public function run(): void
    {
        $locales = $this->installedLocales();

        foreach (Languages::getLanguageCodes() as $code) {
            if (strlen($code) !== 2) {
                continue;
            }

            $names = [];

            foreach ($locales as $locale) {
                $names[$locale] = Languages::getName($code, $locale);
            }

            try {
                $alpha3 = Languages::getAlpha3Code($code);
            } catch (MissingResourceException) {
                $alpha3 = null;
            }

            $this->seedRow(Language::class, ['code' => $code], ['name' => $names], ['alpha3_code' => $alpha3]);
        }
    }
}
