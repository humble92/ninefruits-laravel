<?php

namespace App\Support\Pdf;

/**
 * Renders HTML to PDF. Chromium based so that mixed Korean, Chinese and Latin text uses the
 * same fonts as the browser (porting/decisions/0004-pdf-rendering.md).
 */
interface PdfRenderer
{
    public function render(string $html, PdfOptions $options = new PdfOptions): string;

    /**
     * @param  array<string, mixed>  $data
     */
    public function renderView(string $view, array $data = [], PdfOptions $options = new PdfOptions): string;
}
