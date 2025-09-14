<?php

namespace App\Services\Webling\Letter;

use App\Services\Webling\Letter\Dto\LetterDraft;
use App\Services\Webling\Letter\Dto\LetterOptions;
use App\Services\Webling\Letter\Dto\QrInvoiceOptions;
use Closure;

class LetterBuilder
{
    protected string $headerText = '';

    protected string $body1 = '';

    protected string $body2 = '';

    protected QrInvoiceOptions $qr;

    protected LetterOptions $options;

    public function __construct()
    {
        $this->qr = new QrInvoiceOptions;
        $this->options = new LetterOptions;
    }

    public function header(string $text): self
    {
        $this->headerText = $text;

        return $this;
    }

    public function body1(string $text): self
    {
        $this->body1 = $text;

        return $this;
    }

    public function body2(string $text): self
    {
        $this->body2 = $text;

        return $this;
    }

    /**
     * Configure QR invoice options via a callback.
     *
     * @param  Closure(QrInvoiceOptions):void  $configure
     */
    public function withQrInvoice(Closure $configure): self
    {
        $configure($this->qr);

        return $this;
    }

    public function options(?LetterOptions $options): self
    {
        if ($options !== null) {
            $this->options = $options;
        }

        return $this;
    }

    public function build(): LetterDraft
    {
        return new LetterDraft(
            headerText: $this->headerText,
            bodyIntro: $this->body1,
            bodyOutro: $this->body2,
            qr: $this->qr,
            options: $this->options,
        );
    }
}
