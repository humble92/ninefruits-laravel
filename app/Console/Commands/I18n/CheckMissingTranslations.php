<?php

namespace App\Console\Commands\I18n;

use App\Console\Commands\AuditedCommand;
use App\Support\I18n\TranslationCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('i18n:missing {locale? : Only check this locale} {--strict : Exit with code 2 when keys are missing}')]
#[Description('List translation keys missing compared with the fallback locale')]
class CheckMissingTranslations extends Command
{
    public function handle(TranslationCatalog $catalog): int
    {
        $reference = (string) config('app.fallback_locale');
        $locales = $this->argument('locale') ? [(string) $this->argument('locale')] : array_diff($catalog->locales(), [$reference]);
        $missingTotal = 0;

        foreach ($locales as $locale) {
            $missing = $catalog->missing($locale, $reference);
            $obsolete = $catalog->obsolete($locale, $reference);
            $missingTotal += count($missing);

            $this->components->twoColumnDetail("<fg=cyan>{$locale}</>", sprintf('%d missing, %d obsolete', count($missing), count($obsolete)));

            foreach ($missing as $key) {
                $this->line("  <fg=red>missing</>  {$key}");
            }

            foreach ($obsolete as $key) {
                $this->line("  <fg=yellow>obsolete</> {$key}");
            }
        }

        return $missingTotal > 0 && $this->option('strict') ? AuditedCommand::PROBLEMS_FOUND : self::SUCCESS;
    }
}
