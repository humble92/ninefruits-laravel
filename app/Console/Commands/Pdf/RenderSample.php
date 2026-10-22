<?php

namespace App\Console\Commands\Pdf;

use App\Support\Pdf\PdfRenderer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('pdf:sample {path=pdf-sample.pdf : Path on the local disk}')]
#[Description('Render a mixed Korean, Chinese and English sample to check PDF fonts')]
class RenderSample extends Command
{
    public function handle(PdfRenderer $renderer): int
    {
        $path = (string) $this->argument('path');

        Storage::disk('local')->put($path, $renderer->renderView('pdf.sample'));

        $this->components->info('Written to '.Storage::disk('local')->path($path));

        return self::SUCCESS;
    }
}
