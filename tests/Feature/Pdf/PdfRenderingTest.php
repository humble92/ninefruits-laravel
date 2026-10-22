<?php

use App\Support\Pdf\PdfOptions;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('sends HTML and page setup to Gotenberg', function () {
    Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.7 fake')]);

    $pdf = app(PdfRenderer::class)->render('<p>자료구조</p>', new PdfOptions(landscape: true));

    expect($pdf)->toBe('%PDF-1.7 fake');

    Http::assertSent(function (Request $request) {
        $fields = collect($request->data())->pluck('contents', 'name');

        return str_ends_with($request->url(), '/forms/chromium/convert/html')
            && $fields['files'] === '<p>자료구조</p>'
            && $fields['landscape'] === 'true'
            && $fields['paperWidth'] === '8.27';
    });
});

it('fails loudly when rendering fails', function () {
    Http::fake(['*' => Http::response('error', 500)]);

    app(PdfRenderer::class)->render('<p>x</p>');
})->throws(RequestException::class);

it('renders mixed Korean, Chinese and English with embedded CJK fonts', function () {
    try {
        Http::timeout(2)->get(config('services.gotenberg.url').'/health')->throw();
    } catch (Throwable) {
        $this->markTestSkipped('Gotenberg is not reachable.');
    }

    $pdf = app(PdfRenderer::class)->renderView('pdf.sample');

    expect($pdf)->toStartWith('%PDF')
        ->and($pdf)->toContain('NotoSansCJKkr')
        ->and($pdf)->toContain('NotoSansCJKsc');
})->group('integration');
