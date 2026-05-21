<?php

use App\Components\AdminSettings;
use App\Models\User;
use App\Settings\WeblingApiSettings;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('requires authentication to view the admin settings page', function () {

    assertGuest();

    get('/admin/einstellungen')->assertRedirect();
});

it('renders the admin settings page and component for authenticated users', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = get('/admin/einstellungen');

    $response->assertOk();
    Livewire::test(AdminSettings::class)->assertStatus(200);
});

it('can update settings via the AdminSettings component and persists them', function (): void {
    // Prepare settings infrastructure
    Artisan::call('settings:discover');
    Artisan::call('migrate');

    // Seed some initial values
    /** @var WeblingApiSettings $settings */
    $settings = app(WeblingApiSettings::class);
    $settings->api_url = 'https://initial.webling.ch';
    $settings->api_key = 'old-key-but-32-chars-loooooooong'; // ggignore
    $settings->accounting_period_id = 2024;
    $settings->debit_account_id = 3000;
    $settings->credit_account_id = 7000;
    $settings->save();

    $user = User::factory()->create();
    actingAs($user);

    $component = Livewire::test(AdminSettings::class);

    $class = WeblingApiSettings::class;

    // Update multiple fields in one class and save once
    $component
        ->set("values.$class.api_url", 'https://changed.webling.ch')
        ->set("values.$class.debit_account_id", '4321')
        ->set("values.$class.api_key", 'new-secret-key-but-32-chars-long')
        ->call('saveClass', $class)
        ->call('commitPending');

    expect(app(WeblingApiSettings::class)->api_url)->toBe('https://changed.webling.ch')
        ->and(app(WeblingApiSettings::class)->debit_account_id)->toBe(4321)
        ->and(app(WeblingApiSettings::class)->api_key)->toBe('new-secret-key-but-32-chars-long');

    // Component state refreshes after save
    $component->assertSet("values.$class.api_url", 'https://changed.webling.ch');
});

it('validates invalid values and shows errors without saving', function (): void {
    Artisan::call('settings:discover');
    Artisan::call('migrate');

    /** @var WeblingApiSettings $settings */
    $settings = app(WeblingApiSettings::class);
    $settings->api_url = 'https://okay.webling.ch';
    $settings->api_key = 'key-but-32-chars-loooooooooooong'; // ggignore
    $settings->accounting_period_id = 1;
    $settings->debit_account_id = 1;
    $settings->credit_account_id = 1;
    $settings->save();

    $user = User::factory()->create();
    actingAs($user);

    $class = WeblingApiSettings::class;

    Livewire::test(AdminSettings::class)
        ->set("values.$class.api_url", 'https://not-ok.other-example.com')
        ->call('saveClass', $class)
        ->assertHasErrors(["values.$class.api_url" => 'regex']);

    // Value should remain unchanged in persisted settings
    expect(app(WeblingApiSettings::class)->api_url)->toBe('https://okay.webling.ch');
});
