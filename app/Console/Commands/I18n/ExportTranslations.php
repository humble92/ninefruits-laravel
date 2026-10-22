<?php

namespace App\Console\Commands\I18n;

use App\Support\I18n\TranslationCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('i18n:export {locale} {path : JSON file to write} {--missing : Only keys missing in the locale}')]
#[Description('Export translations as JSON for translators: {"key": {"source": "...", "translation": "..."}}')]
class ExportTranslations extends Command
{
    public function handle(TranslationCatalog $catalog): int
    {
        $locale = (string) $this->argument('locale');
        $source = $catalog->flatten((string) config('app.fallback_locale'));
        $target = $catalog->flatten($locale);
        $keys = $this->option('missing') ? $catalog->missing($locale, (string) config('app.fallback_locale')) : array_keys($source);

        $document = [];

        foreach ($keys as $key) {
            $document[$key] = ['source' => $source[$key], 'translation' => $target[$key] ?? ''];
        }

        File::put((string) $this->argument('path'), (string) json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->components->info(sprintf('Exported %d keys to %s', count($document), $this->argument('path')));

        return self::SUCCESS;
    }
}
