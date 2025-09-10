<?php

use App\Services\SettingsService;
use App\Settings\WeblingApiSettings;
use Illuminate\Support\Facades\Artisan;

it('returns faked values when all properties are faked', function (): void {
    // Discover settings classes and ensure DB is ready (settings table)
    Artisan::call('settings:discover');
    Artisan::call('migrate');

    // Fake all properties so no repository calls are needed
    WeblingApiSettings::fake([
        'api_url' => 'https://fake.example/v1',
        'api_key' => 'super-secret-key',
        'accounting_period_id' => 2025,
        'debit_account_id' => 4000,
        'credit_account_id' => 8400,
    ]);

    $service = app(SettingsService::class);

    $result = $service->getAllSettings();

    $group = $result[WeblingApiSettings::class] ?? null;

    expect($group)->not->toBeNull();
    expect($group['group'] ?? null)->toBe('weblingApi');

    $settings = $group['settings'] ?? [];

    // Values come from the fake
    expect($settings['api_url']['value'] ?? null)->toBe('https://fake.example/v1')
        ->and($settings['api_key']['value'] ?? null)->toBe('super-secret-key')
        ->and($settings['accounting_period_id']['value'] ?? null)->toBe(2025)
        ->and($settings['debit_account_id']['value'] ?? null)->toBe(4000)
        ->and($settings['credit_account_id']['value'] ?? null)->toBe(8400)
        ->and($settings['api_url']['type'] ?? null)->toBe('string')
        ->and($settings['api_key']['type'] ?? null)->toBe('string')
        ->and($settings['accounting_period_id']['type'] ?? null)->toBe('int')
        ->and($settings['debit_account_id']['type'] ?? null)->toBe('int')
        ->and($settings['credit_account_id']['type'] ?? null)->toBe('int')
        ->and($settings['api_url']['description'] ?? null)->toBeString()->not->toBe('')
        ->and($settings['api_key']['encrypted'] ?? null)->toBeTrue()
        ->and($settings['api_url']['encrypted'] ?? null)->toBeFalse()
        ->and($settings)->toHaveKeys([
            'api_url', 'api_key', 'accounting_period_id', 'debit_account_id', 'credit_account_id',
        ]);
});
