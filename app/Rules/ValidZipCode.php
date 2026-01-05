<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidZipCode implements ValidationRule
{
    public function __construct(private string $countryOfResidence) {}

    /**
     * Validate the given attribute with the given closure.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = match ($this->countryOfResidence) {
            'AT' => '/^\d{4}$/',
            'DE' => '/^\d{5}$/',
            'CH' => '/^[1-9]\d{3}$/',
            default => null,
        };

        if ($pattern && ! preg_match($pattern, (string) $value)) {
            $fail('Ungültige Postleitzahl');
        }
    }
}
