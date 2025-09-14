<?php

namespace App\Services\Webling\Letter\Dto;

/**
 * High-level letter options for rendering.
 */
class LetterOptions
{
    public function __construct(
        public int $globalFontSize = 16,
        public string $globalFontFamily = "'Source Sans Pro', sans-serif",
        public bool $showHeader = true,
        public bool $showFooter = false,
        public bool $showQrInvoice = true,
        public bool $showPaymentinstruction = false,
        public string $letterType = 'invoice',
        public ?PageMargin $pageMargin = null,
    ) {
        $this->pageMargin ??= new PageMargin;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'globalFontSize' => $this->globalFontSize,
            'globalFontFamily' => $this->globalFontFamily,
            'showHeader' => $this->showHeader,
            'showFooter' => $this->showFooter,
            'showQrInvoice' => $this->showQrInvoice,
            'showPaymentinstruction' => $this->showPaymentinstruction,
            'letterType' => $this->letterType,
            'pageMargin' => $this->pageMargin?->toArray(),
        ];
    }
}
