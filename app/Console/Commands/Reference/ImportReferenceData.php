<?php

namespace App\Console\Commands\Reference;

use App\Console\Commands\AuditedCommand;
use App\Filament\Admin\Imports\ReferenceImporters;
use Filament\Actions\Imports\ImportDispatcher;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use League\Csv\Reader;

/**
 * Large reference data files. Uses the same importers and queued batch as the admin
 * screen, so validation, matching by code and failed row reports are identical.
 */
#[Signature('reference:import
    {type : org_units, programs, buildings, rooms or lookup_values}
    {file : CSV file with a header row (UTF-8)}
    {--chunk=100 : Rows per queued job}
    {--dry-run : Check the header and count rows without importing}')]
#[Description('Import reference data from a CSV file (queued, same importers as the admin screen). Requires --actor.')]
class ImportReferenceData extends AuditedCommand
{
    public function handle(ImportDispatcher $dispatcher): int
    {
        $type = (string) $this->argument('type');
        $importer = ReferenceImporters::TYPES[$type] ?? null;

        if ($importer === null) {
            $this->components->error('Unknown type. Use one of: '.implode(', ', array_keys(ReferenceImporters::TYPES)).'.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('file');

        if (! is_readable($path)) {
            $this->components->error("Cannot read [{$path}].");

            return self::FAILURE;
        }

        $actor = $this->actor();

        if ($actor === null) {
            $this->components->error('--actor is required: the import is recorded under that account and its completion notice goes there.');

            return self::FAILURE;
        }

        $csv = Reader::createFromPath($path);
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        $columns = array_map(fn ($column) => $column->getName(), $importer::getColumns());

        $missing = array_values(array_filter(
            $importer::getColumns(),
            fn ($column) => $column->isMappingRequired() && ! in_array($column->getName(), $header, true),
        ));
        $unknown = array_values(array_diff($header, $columns));

        foreach ($unknown as $column) {
            $this->components->warn("Ignored column [{$column}].");
        }

        if ($missing !== []) {
            $this->components->error('Missing required columns: '.implode(', ', array_map(fn ($column) => $column->getName(), $missing)).'.');

            return self::FAILURE;
        }

        $rows = iterator_to_array($csv->getRecords(), false);
        $this->recordResult(['type' => $type, 'file' => basename($path), 'rows' => count($rows)]);

        if ($this->isDryRun()) {
            $this->components->info(sprintf('Dry run: %d row(s) with a valid header.', count($rows)));

            return self::SUCCESS;
        }

        $import = new Import;
        $import->user()->associate($actor);
        $import->file_name = basename($path);
        $import->file_path = realpath($path) ?: $path;
        $import->importer = $importer;
        $import->total_rows = count($rows);
        $import->save();

        $columnMap = array_combine(
            array_values(array_intersect($columns, $header)),
            array_values(array_intersect($columns, $header)),
        );

        $dispatcher->dispatch($import, array_chunk($rows, max(1, (int) $this->option('chunk'))), $columnMap, [], ImportCsv::class, 'web');

        $import->refresh();
        $failed = $import->getFailedRowsCount();
        $this->recordResult(['import_id' => $import->id, 'failed_rows' => $failed]);

        if ($import->completed_at !== null) {
            $this->components->info(sprintf('%d imported, %d failed.', $import->successful_rows, $failed));

            foreach ($import->failedRows()->limit(20)->get() as $row) {
                $this->line('  <fg=red>failed</> '.json_encode($row->getAttribute('data'), JSON_UNESCAPED_UNICODE).' '.$row->getAttribute('validation_error'));
            }

            return $failed > 0 ? self::PROBLEMS_FOUND : self::SUCCESS;
        }

        $this->components->info("Queued import #{$import->id} ({$import->total_rows} rows). The result is sent to {$actor->email} in the admin panel notifications.");

        return self::SUCCESS;
    }
}
