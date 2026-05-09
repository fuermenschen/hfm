<?php

declare(strict_types=1);

namespace App\Support\Datatable;

use Illuminate\Support\Facades\Date;

class DatatableValueFormatter
{
    public function moneyOrUnlimited(float|int|string|null $value, string $unlimitedLabel = 'unbegrenzt'): string
    {
        if (! is_numeric($value) || (float) $value <= 0.0) {
            return $unlimitedLabel;
        }

        return $this->money($value);
    }

    public function money(float|int|string|null $value, string $fallback = '-'): string
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        $profile = $this->numberProfile();
        $formatted = number_format((float) $value, 2, $profile['decimal_separator'], $profile['thousands_separator']);

        return $profile['currency_prefix'].' '.$formatted;
    }

    /**
     * @return array{thousands_separator:string, decimal_separator:string, currency_prefix:string}
     */
    protected function numberProfile(): array
    {
        $locale = str_replace('-', '_', (string) app()->getLocale());

        return match ($locale) {
            'de', 'de_CH', 'fr_CH', 'it_CH' => [
                'thousands_separator' => "'",
                'decimal_separator' => '.',
                'currency_prefix' => 'Fr.',
            ],
            default => [
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'currency_prefix' => 'Fr.',
            ],
        };
    }

    public function date(mixed $value, string $fallback = '-'): string
    {
        return $this->formatDateValue($value, 'd.m.Y', $fallback);
    }

    protected function formatDateValue(mixed $value, string $format, ?string $fallback): ?string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            return Date::parse($value)->format($format);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function dateOrNull(mixed $value): ?string
    {
        return $this->formatDateValue($value, 'd.m.Y', null);
    }

    public function dateTime(mixed $value, string $fallback = '-'): string
    {
        return $this->formatDateValue($value, 'd.m.Y H:i', $fallback);
    }

    public function dateTimeOrNull(mixed $value): ?string
    {
        return $this->formatDateValue($value, 'd.m.Y H:i', null);
    }

    public function truncate(?string $value, int $length = 42, string $fallback = '-'): string
    {
        $text = $this->text($value, $fallback);

        if ($text === $fallback || mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1).'…';
    }

    public function text(?string $value, string $fallback = '-'): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }
}
