<?php

namespace App\Support\Pdf;

/**
 * Page setup. Sizes in inches (A4 by default), as Chromium expects.
 */
final readonly class PdfOptions
{
    public function __construct(
        public float $paperWidth = 8.27,
        public float $paperHeight = 11.69,
        public float $margin = 0.6,
        public bool $landscape = false,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toGotenbergFields(): array
    {
        return [
            'paperWidth' => (string) $this->paperWidth,
            'paperHeight' => (string) $this->paperHeight,
            'marginTop' => (string) $this->margin,
            'marginBottom' => (string) $this->margin,
            'marginLeft' => (string) $this->margin,
            'marginRight' => (string) $this->margin,
            'landscape' => $this->landscape ? 'true' : 'false',
            'printBackground' => 'true',
        ];
    }
}
