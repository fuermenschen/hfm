<?php

use App\Settings\WeblingApiSettings;

it('leaves payment accounts unconfigured after migration', function (): void {
    $settings = app(WeblingApiSettings::class);

    expect($settings->bank_account_id)->toBeNull()
        ->and($settings->excess_income_account_id)->toBeNull();
});

it('accepts positive or unconfigured payment account IDs', function (mixed $value): void {
    $data = [
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 10,
        'debit_account_id' => 2,
        'credit_account_id' => 8,
        'bank_account_id' => $value,
        'excess_income_account_id' => $value,
    ];

    $validation = validator($data, WeblingApiSettings::rules());

    expect($validation->passes())->toBeTrue();
})->with([null, '', 2999, '2999']);

it('rejects invalid payment account IDs', function (string $field, mixed $value): void {
    $data = [
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 10,
        'debit_account_id' => 2,
        'credit_account_id' => 8,
        $field => $value,
    ];

    $validation = validator($data, WeblingApiSettings::rules());

    expect($validation->errors()->has($field))->toBeTrue();
})->with([
    'zero bank ID' => ['bank_account_id', 0],
    'negative bank ID' => ['bank_account_id', -1],
    'fractional bank ID' => ['bank_account_id', 29.99],
    'named bank account' => ['bank_account_id', 'Bank'],
    'zero excess ID' => ['excess_income_account_id', 0],
    'negative excess ID' => ['excess_income_account_id', -1],
    'fractional excess ID' => ['excess_income_account_id', 29.93],
    'named excess account' => ['excess_income_account_id', 'Ertrag'],
]);
