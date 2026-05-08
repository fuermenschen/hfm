<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter;

use Illuminate\Support\Facades\Validator;

class LetterSchemaValidator
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function validate(array $payload): void
    {
        $rules = [
            'options' => ['required', 'array'],
            'header' => ['nullable', 'array'],
            'body' => ['required', 'array'],
            'qrInvoice' => ['nullable', 'array'],
        ];

        $v = Validator::make($payload, $rules);
        $v->validate();
    }
}
