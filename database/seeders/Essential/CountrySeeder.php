<?php

namespace Database\Seeders\Essential;

use App\Models\Institution\Country;
use Database\Seeders\Essential\Concerns\SeedsTranslatedRows;
use Illuminate\Database\Seeder;
use Symfony\Component\Intl\Countries;

/**
 * ISO 3166-1 countries with names in every installed language (symfony/intl data).
 */
class CountrySeeder extends Seeder
{
    use SeedsTranslatedRows;

    public function run(): void
    {
        $locales = $this->installedLocales();

        foreach (Countries::getCountryCodes() as $code) {
            $names = [];

            foreach ($locales as $locale) {
                $names[$locale] = Countries::getName($code, $locale);
            }

            $this->seedRow(Country::class, ['code' => $code], ['name' => $names], [
                'alpha3_code' => Countries::getAlpha3Code($code),
                'numeric_code' => Countries::getNumericCode($code),
            ]);
        }
    }
}
