<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter\Dto;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Immutable letter input. Settings are resolved before construction.
 */
readonly class LetterDraft
{
    public function __construct(
        public string $headerText = '',
        public string $bodyIntro = '',
        public string $bodyOutro = '',
        public ?CarbonInterface $date = null,
        // Temporarily unused in active Webling invoice flow.
        // TODO(dead-code): Remove ignore when due date is wired into rendered letter payload again.
        // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
        public ?CarbonInterface $dueDate = null,
        /** @var array<string,mixed> */
        public array $qr = [],
        /** @var array<string,mixed> */
        public array $options = [],
    ) {}

    /**
     * @param  array<string,mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        $letter = $snapshot['letter'] ?? [];
        if (! is_array($letter)) {
            $letter = [];
        }

        $qr = $letter['qr_invoice'] ?? [];
        $options = $letter['options'] ?? [];
        $date = $letter['date'] ?? null;

        return new self(
            headerText: (string) ($letter['header_text'] ?? ''),
            bodyIntro: (string) ($letter['body_intro'] ?? ''),
            bodyOutro: (string) ($letter['body_outro'] ?? ''),
            date: $date !== null ? Date::parse((string) $date) : null,
            qr: is_array($qr) ? $qr : [],
            options: is_array($options) ? $options : [],
        );
    }
}
