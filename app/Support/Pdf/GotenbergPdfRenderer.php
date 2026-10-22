<?php

namespace App\Support\Pdf;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\View;

class GotenbergPdfRenderer implements PdfRenderer
{
    public function __construct(private readonly HttpFactory $http) {}

    public function render(string $html, PdfOptions $options = new PdfOptions): string
    {
        return $this->http
            ->baseUrl((string) config('services.gotenberg.url'))
            ->timeout((int) config('services.gotenberg.timeout'))
            ->attach('files', $html, 'index.html', ['Content-Type' => 'text/html; charset=utf-8'])
            ->post('/forms/chromium/convert/html', $options->toGotenbergFields())
            ->throw()
            ->body();
    }

    public function renderView(string $view, array $data = [], PdfOptions $options = new PdfOptions): string
    {
        return $this->render(View::make($view, $data)->render(), $options);
    }
}
