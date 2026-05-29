<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter;

use App\Services\Webling\Letter\Dto\LetterDraft;
use App\Services\Webling\Letter\Template\InvoiceLetterTemplate;

class LetterRenderer
{
    // Webling letter pipeline currently not active in production flows.
    // TODO(dead-code): Remove temporary ignores when Webling letter generation is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function __construct(public InvoiceLetterTemplate $template = new InvoiceLetterTemplate) {}

    /**
     * @return array<string,mixed>
     */
    public function render(LetterDraft $draft): array
    {
        return $this->template->render($draft);
    }
}
