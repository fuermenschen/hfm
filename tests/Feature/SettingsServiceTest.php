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

it('getAllSettings returns metadata and current values from repository', function (): void {
    Artisan::call('settings:discover');
    Artisan::call('migrate');

    // Seed persisted values
    $s = app(WeblingApiSettings::class);
    $s->api_url = 'https://repo.example/v1';
    $s->api_key = 'repo-key';
    $s->accounting_period_id = 2023;
    $s->debit_account_id = 4100;
    $s->credit_account_id = 8100;
    $s->save();

    $service = app(SettingsService::class);
    $result = $service->getAllSettings();

    $group = $result[WeblingApiSettings::class] ?? null;
    expect($group)->not->toBeNull();

    expect($group['group'] ?? null)->toBe('weblingApi')
        ->and($group['title'] ?? null)->toBe('Webling API')
        ->and($group['description'] ?? null)->toBeString()->not->toBe('');

    $settings = $group['settings'] ?? [];

    expect($settings['api_key']['encrypted'] ?? null)->toBeTrue()
        ->and($settings['api_url']['value'] ?? null)->toBe('https://repo.example/v1')
        ->and($settings['debit_account_id']['value'] ?? null)->toBe(4100)
        ->and($settings['api_url']['title'] ?? null)->toBe('Webling API URL')
        ->and($settings['api_url']['rules'] ?? null)->toBe(['required', 'regex:/^https:\/\/[a-zA-Z0-9\-]+\.webling\.ch$/']);
});

it('save persists values with type coercion and ignores invalid classes/props', function (): void {
    Artisan::call('settings:discover');
    Artisan::call('migrate');

    $service = app(SettingsService::class);

    // Save with coercion and extra noise
    $service->save([
        WeblingApiSettings::class => [
            'api_url' => 'https://coerce.example/v3',
            'debit_account_id' => '5555', // string -> int
            'unknown_prop' => 'ignored',
        ],
        'App\\Settings\\DoesNotExist' => ['foo' => 'bar'],
    ]);

    $fresh = app(WeblingApiSettings::class);

    expect($fresh->api_url)->toBe('https://coerce.example/v3')
        ->and($fresh->debit_account_id)->toBe(5555);
});
