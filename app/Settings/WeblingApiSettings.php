<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WeblingApiSettings extends Settings
{
    public string $api_url;

    public string $api_key;

    public int $accounting_period_id;

    public int $debit_account_id;

    public int $credit_account_id;

    public static function group(): string
    {
        return 'weblingApi';
    }

    public static function encrypted(): array
    {
        return ['api_key'];
    }

    public static function descriptions(): array
    {
        return [
            'api_url' => 'The base URL of the Webling API (e.g., https://api.yourdomain.com/v1)',
            'api_key' => 'Your Webling API key for authentication',
            'accounting_period_id' => 'The ID of the accounting period in Webling',
            'debit_account_id' => 'The ID of the debit account in Webling',
            'credit_account_id' => 'The ID of the credit account in Webling',
        ];
    }
}
