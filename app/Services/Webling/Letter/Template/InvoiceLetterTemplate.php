<?php

namespace App\Services\Webling\Letter\Template;

use App\Services\Webling\Letter\Dto\LetterDraft;

/**
 * Minimal invoice template mapping builder slots to Webling letter JSON shape.
 */
class InvoiceLetterTemplate
{
    /**
     * @return array<string,mixed>
     */
    public function render(LetterDraft $draft): array
    {
        $options = $draft->options?->toArray() ?? [];
        $qr = $draft->qr?->toArray() ?? [];

        $headerHtml = '';
        if ($options['showHeader'] ?? true) {
            $escaped = nl2br(e($draft->headerText));
            $headerHtml = "<div><span style=\"font-size: 20px;\"><strong>{$escaped}</strong></span></div>";
        }

        $due = $draft->dueDate ? $draft->dueDate->format('Y-m-d') : '';

        $bodyIntroHtml = '<div>'.nl2br(e($draft->bodyIntro)).'</div>';
        $bodyOutroHtml = '<div>'.nl2br(e($draft->bodyOutro)).'</div>';

        return [
            'options' => $options,
            'qrInvoice' => $qr,
            'paymentinstruction' => null,
            'header' => [
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 184,
                        'id' => 'hfm-header',
                        'type' => 'header',
                        'padding' => ['top' => 38, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => [
                            'backgroundImageUrl' => '',
                            'backgroundImageSize' => 'cover',
                            'backgroundImagePosition' => 'top',
                        ],
                        'content' => ['html' => $headerHtml],
                    ],
                ],
            ],
            'footer' => [],
            'body' => [
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-date',
                        'type' => 'html',
                        'padding' => ['top' => 4, 'right' => 0, 'bottom' => 4, 'left' => 0],
                        'options' => [],
                        'content' => ['html' => "<div>Fällig bis: {$due}</div>"],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-intro',
                        'type' => 'html',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => [],
                        'content' => ['html' => $bodyIntroHtml],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-outro',
                        'type' => 'html',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => [],
                        'content' => ['html' => $bodyOutroHtml],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 0,
                        'id' => 'hfm-invoiceitems',
                        'type' => 'invoiceitems',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => ['design' => 'simple'],
                        'content' => ['html' => ''],
                    ],
                ],
            ],
        ];
    }
}
