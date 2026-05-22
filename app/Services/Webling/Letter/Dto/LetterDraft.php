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
        // Temporarily unused in active Webling invoice flow.
        // TODO(dead-code): Remove ignore when due date is wired into rendered letter payload again.
        // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
        public ?CarbonInterface $dueDate = null,
        public ?QrInvoiceOptions $qr = null,
        public ?LetterOptions $options = null,
    ) {
        $this->qr ??= new QrInvoiceOptions;
        $this->options ??= new LetterOptions;
    }
}
