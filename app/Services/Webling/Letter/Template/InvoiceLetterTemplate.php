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
                        'start' => 6,
                        'width' => 6,
                        'minHeight' => 93,
                        'id' => 'hfm-address',
                        'type' => 'address',
                        'padding' => ['top' => 1.9, 'right' => 0, 'bottom' => 1.9, 'left' => 0],
                        'options' => [
                            'showSender' => true,
                        ],
                        'content' => [
                            'html' => '<div><span class="fr-deletable webling-placeholder webling-simple-placeholder" contenteditable="false" data-webling-placeholder="%7B%22type%22%3A%22simple%22%2C%22field%22%3A%22Rechnung%3AAdresse%22%7D">{{Rechnung:Adresse}}</span>&nbsp;</div>',
                            'sender' => 'Höhenmeter für Menschen, hfm-winti.ch',
                        ],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-city-date',
                        'type' => 'html',
                        'padding' => ['top' => 4.94, 'right' => 0, 'bottom' => 4.94, 'left' => 0],
                        'options' => [],
                        'content' => [
                            'html' => '<div style="text-align: left;">Winterthur, <span class="fr-deletable webling-placeholder webling-simple-placeholder" contenteditable="false" data-webling-placeholder="%7B%22type%22%3A%22simple%22%2C%22field%22%3A%22D.%20MMMM%20YYYY%22%7D">{{D. MMMM YYYY}}</span></div>',
                        ],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-title',
                        'type' => 'html',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                        'options' => [],
                        'content' => [
                            'html' => '<div><span class="fr-deletable webling-placeholder webling-simple-placeholder" contenteditable="false" data-webling-placeholder="%7B%22type%22%3A%22simple%22%2C%22field%22%3A%22Rechnung%3ATitel%22%7D" style="font-size: 24px;"><strong><span><span class="fr-deletable webling-placeholder webling-simple-placeholder" contenteditable="false" data-webling-placeholder="%7B%22type%22%3A%22simple%22%2C%22field%22%3A%22Rechnung%3ATitel%22%7D">{{Rechnung:Titel}}</span><span class="fr-marker"></span></span></strong></span></div>',
                        ],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-intro',
                        'type' => 'html',
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 5, 'left' => 0],
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
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 5, 'left' => 0],
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
                        'padding' => ['top' => 0, 'right' => 0, 'bottom' => 5, 'left' => 0],
                        'options' => [],
                        'content' => ['html' => $bodyOutroHtml],
                    ],
                ],
                [
                    [
                        'start' => 0,
                        'width' => 12,
                        'minHeight' => 20,
                        'id' => 'hfm-pagebreak',
                        'type' => 'pagebreak',
                        'padding' => ['top' => 5, 'right' => 0, 'bottom' => 5, 'left' => 0],
                        'options' => [],
                        'content' => ['html' => ''],
                    ],
                ],
            ],
        ];
    }
}
