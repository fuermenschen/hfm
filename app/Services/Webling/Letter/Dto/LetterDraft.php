<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter\Dto;

use Carbon\CarbonInterface;

/**
 * Draft produced by the LetterBuilder holding end-user provided content.
 */
class LetterDraft
{
    public function __construct(
        public string $headerText = '',
        public string $bodyIntro = '',
        public string $bodyOutro = '',
        public ?CarbonInterface $dueDate = null,
        public ?QrInvoiceOptions $qr = null,
        public ?LetterOptions $options = null,
    ) {
        $this->qr ??= new QrInvoiceOptions;
        $this->options ??= new LetterOptions;
    }
}
