<?php

namespace App\Console\Commands\I18n;

use App\Console\Commands\AuditedCommand;
use App\Support\I18n\TranslationCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\File;

#[Signature('i18n:import {locale} {path : JSON file from i18n:export} {--dry-run : List the translations that would be written}')]
#[Description('Import translated JSON into lang/{locale}. Only keys that exist in the fallback locale are imported.')]
class ImportTranslations extends AuditedCommand
{
    public function handle(TranslationCatalog $catalog): int
    {
        $locale = (string) $this->argument('locale');

        if (! preg_match('/^[a-z]{2,3}(_[A-Z][A-Za-z]{1,3})?$/', $locale)) {
            $this->components->error("Invalid locale [{$locale}].");

            return self::FAILURE;
        }

        $document = json_decode((string) File::get((string) $this->argument('path')), true);

        if (! is_array($document)) {
            $this->components->error('The file is not valid JSON.');

            return self::FAILURE;
        }

        $known = $catalog->flatten((string) config('app.fallback_locale'));
        $entries = [];

        foreach ($document as $key => $entry) {
            $translation = is_array($entry) ? ($entry['translation'] ?? '') : $entry;

            if (isset($known[$key]) && is_string($translation) && $translation !== '') {
                $entries[$key] = $translation;
            }
        }

        $this->recordResult(['locale' => $locale, 'translations' => count($entries)]);

        if ($this->isDryRun()) {
            foreach (array_keys($entries) as $key) {
                $this->line("  {$key}");
            }

            $this->components->info(sprintf('Dry run: %d translation(s) would be written to lang/%s.', count($entries), $locale));

            return self::SUCCESS;
        }

        $count = $catalog->write($locale, $entries);

        $this->components->info("Imported {$count} translations into lang/{$locale}.");

        return self::SUCCESS;
    }
}
