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

    public static function settingsDetails(): array
    {
        $title = 'Webling API';
        $description = 'Einstellungen für die Schnittstelle zu Webling.';

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    public static function rules(): array
    {
        return [
            'api_url' => 'required|url',
            'api_key' => 'required',
            'accounting_period_id' => 'required|integer',
            'debit_account_id' => 'required|integer',
            'credit_account_id' => 'required|integer',
        ];
    }

    public static function titles(): array
    {
        return [
            'api_url' => 'Webling API URL',
            'api_key' => 'Webling API Key',
            'accounting_period_id' => 'ID der Abrechnungsperiode',
            'debit_account_id' => 'Sollkonto ID',
            'credit_account_id' => 'Habenkonto ID',
        ];
    }

    public static function descriptions(): array
    {
        return [
            'api_url' => 'Die Basis-URL zur Webling API (z.B., https://api.yourdomain.com/v1)',
            'api_key' => 'Der API-Schlüssel',
            'accounting_period_id' => 'Die ID der aktuellen Abrechnungsperiode (Achtung: die ID, nicht das Jahr selbst).',
            'debit_account_id' => 'Die ID der Sollkontos (Achtung: die ID, nicht die Kontonummer selbst).',
            'credit_account_id' => 'Die ID der Habenkontos (Achtung: die ID, nicht die Kontonummer selbst).',
        ];
    }
}
