<?php

namespace App\Support\I18n;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Reads and writes the application's group translation files (lang/{locale}/{group}.php)
 * as flat "group.key" => "text" maps.
 */
class TranslationCatalog
{
    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return collect(File::directories(lang_path()))->map(fn (string $path) => basename($path))->sort()->values()->all();
    }

    /**
     * @return array<string, string>
     */
    public function flatten(string $locale): array
    {
        $entries = [];

        foreach (File::glob(lang_path("{$locale}/*.php")) as $file) {
            $group = basename($file, '.php');
            $values = require $file;

            foreach (Arr::dot(is_array($values) ? $values : []) as $key => $value) {
                if (is_string($value)) {
                    $entries["{$group}.{$key}"] = $value;
                }
            }
        }

        ksort($entries);

        return $entries;
    }

    /**
     * Keys present in the reference locale but missing or empty in the target locale.
     *
     * @return list<string>
     */
    public function missing(string $locale, string $reference): array
    {
        $target = $this->flatten($locale);

        return array_values(array_filter(
            array_keys($this->flatten($reference)),
            fn (string $key) => ! isset($target[$key]) || $target[$key] === '',
        ));
    }

    /**
     * Keys in the target locale that the reference locale no longer has.
     *
     * @return list<string>
     */
    public function obsolete(string $locale, string $reference): array
    {
        return array_values(array_diff(array_keys($this->flatten($locale)), array_keys($this->flatten($reference))));
    }

    /**
     * Writes flat entries back into group files, merging with existing translations.
     *
     * @param  array<string, string>  $entries
     */
    public function write(string $locale, array $entries): int
    {
        $byGroup = [];

        foreach ($entries as $key => $value) {
            [$group, $path] = explode('.', $key, 2) + [1 => null];

            if ($path === null || ! preg_match('/^[a-z][a-z0-9_]*$/', $group)) {
                continue;
            }

            $byGroup[$group][$path] = $value;
        }

        File::ensureDirectoryExists(lang_path($locale));

        foreach ($byGroup as $group => $values) {
            $file = lang_path("{$locale}/{$group}.php");
            $existing = is_file($file) ? Arr::dot((array) require $file) : [];
            $merged = Arr::undot(array_merge($existing, $values));

            File::put($file, "<?php\n\nreturn ".$this->export($merged).";\n");
        }

        return count($entries);
    }

    /**
     * @param  array<mixed>  $values
     */
    private function export(array $values, int $depth = 1): string
    {
        $indent = str_repeat('    ', $depth);
        $lines = [];

        foreach ($values as $key => $value) {
            $lines[] = $indent.var_export($key, true).' => '.(is_array($value) ? $this->export($value, $depth + 1) : var_export($value, true)).',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $depth - 1).']';
    }
}
