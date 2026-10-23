<?php

namespace App\Console\Commands\Media;

use App\Console\Commands\AuditedCommand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Signature('media:prune-orphans {--dry-run : List only}')]
#[Description('Remove media rows whose owner no longer exists and files on the media disk that no row refers to')]
class PruneOrphanMedia extends AuditedCommand
{
    public function handle(): int
    {
        $summary = ['rows' => 0, 'directories' => 0];

        foreach (Media::query()->cursor() as $media) {
            $class = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

            if (class_exists($class) && is_subclass_of($class, Model::class) && $class::query()->whereKey($media->model_id)->exists()) {
                continue;
            }

            $summary['rows']++;
            $this->components->twoColumnDetail("media #{$media->id} ({$media->model_type} #{$media->model_id})", $this->isDryRun() ? 'orphan' : 'deleted');

            if (! $this->isDryRun()) {
                $media->delete();
            }
        }

        $disk = Storage::disk((string) config('media-library.disk_name'));
        $ids = Media::query()->pluck('id')->flip();

        foreach ($disk->directories() as $directory) {
            if (! ctype_digit($directory) || $ids->has((int) $directory)) {
                continue;
            }

            $summary['directories']++;
            $this->components->twoColumnDetail("directory {$directory}", $this->isDryRun() ? 'no media row' : 'deleted');

            if (! $this->isDryRun()) {
                $disk->deleteDirectory($directory);
            }
        }

        $this->recordResult($summary);
        $this->components->info(sprintf('%s%d orphan row(s), %d orphan director(ies).', $this->isDryRun() ? 'Dry run: ' : '', $summary['rows'], $summary['directories']));

        return self::SUCCESS;
    }
}
