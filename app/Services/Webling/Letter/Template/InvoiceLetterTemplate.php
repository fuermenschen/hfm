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
            $headerText = trim($draft->headerText ?? '');

            if ($headerText !== '') {
                // If a custom header is provided, render it at normal 14px font without strong emphasis.
                $escaped = nl2br(e($headerText));
                $headerHtml = "<div><span style=\"font-size: 14px;\">{$escaped}</span></div>";
            } else {
                // Default header when none is provided.
                $headerHtml = '<div style="line-height: 1.25;">'
                    .'<div style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">Höhenmeter für Menschen</div>'
                    .'<div style="font-size: 14px; white-space: pre-line;">'
                    ."Verein für Menschen\n"
                    ."c/o Kai Frehner\n"
                    ."Nelkenstrasse 6\n"
                    .'8400 Winterthur'
                    .'</div>'
                    .'</div>';
            }
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
                        'minHeight' => 0,
                        'id' => 'hfm-invoiceitems',
                        'type' => 'invoiceitems',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => ['design' => 'simple'],
                        'content' => ['html' => ''],
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
            ],
        ];
    }
}
