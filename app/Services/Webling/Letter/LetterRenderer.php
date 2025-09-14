<?php

namespace App\Services\Webling\Letter;

use App\Services\Webling\Letter\Dto\LetterDraft;
use App\Services\Webling\Letter\Template\InvoiceLetterTemplate;

class LetterRenderer
{
    public function __construct(public InvoiceLetterTemplate $template = new InvoiceLetterTemplate) {}

    /**
     * @return array<string,mixed>
     */
    public function render(LetterDraft $draft): array
    {
        return $this->template->render($draft);
    }
}
