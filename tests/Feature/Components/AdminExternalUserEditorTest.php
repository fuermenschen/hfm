<?php

use App\Components\AdminExternalUserEditor;
use App\Components\AdminPersonTable;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('edits external users after confirming changed fields while preserving system identifiers', function (): void {
    $logSpy = Log::spy();

    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Alt',
        'last_name' => 'Name',
        'country_of_residence' => 'DE',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'alt@example.test',
    ]);

    Livewire::test(AdminExternalUserEditor::class)
        ->call('open', $externalUser->id)
        ->set('firstName', 'Neu')
        ->set('lastName', 'Name')
        ->set('address', 'Neue Adresse 2')
        ->set('zipCode', '10115')
        ->set('city', 'Berlin')
        ->set('countryOfResidence', 'DE')
        ->set('phoneNumber', '+41789876543')
        ->set('email', ' NEU@EXAMPLE.TEST ')
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->assertHasNoErrors()
        ->call('confirmSave')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $externalUser->refresh();

    expect($externalUser->first_name)->toBe('Neu')
        ->and($externalUser->address)->toBe('Neue Adresse 2')
        ->and($externalUser->email)->toBe('neu@example.test')
        ->and($externalUser->phone_number)->toBe('+41 78 987 65 43')
        ->and($externalUser->uuid)->not->toBeEmpty()
        ->and($externalUser->public_id)->not->toBeEmpty();

    $logSpy->shouldHaveReceived('info')
        ->with('Admin editor save confirmed.', [
            'editor' => 'AdminExternalUserEditor',
            'fields' => ['firstName', 'address', 'zipCode', 'city', 'phoneNumber', 'email'],
            'external_user_id' => $externalUser->id,
        ])
        ->once();
});

it('closes without saving when no fields changed', function (): void {
    $logSpy = Log::spy();

    $externalUser = ExternalUser::factory()->create([
        'phone_number' => '+41 79 123 45 67',
        'email' => 'alt@example.test',
    ]);

    Livewire::test(AdminExternalUserEditor::class)
        ->call('open', $externalUser->id)
        ->call('save')
        ->assertSet('modalOpen', false)
        ->assertSet('confirmingSave', false)
        ->assertHasNoErrors();

    $externalUser->refresh();

    expect($externalUser->first_name)->toBe($externalUser->getOriginal('first_name'));

    $logSpy->shouldNotHaveReceived('info');
});

it('requires international phone numbers for external user edits', function (): void {
    $externalUser = ExternalUser::factory()->create(['phone_number' => '079 123 45 67']);

    Livewire::test(AdminExternalUserEditor::class)
        ->call('open', $externalUser->id)
        ->assertSet('phoneNumber', '079 123 45 67')
        ->set('lastName', 'Aktualisiert')
        ->call('save')
        ->assertHasErrors(['phoneNumber' => ['phone']]);
});

it('validates external user email uniqueness and country-specific postal codes', function (): void {
    ExternalUser::factory()->create(['email' => 'taken@example.test']);
    $externalUser = ExternalUser::factory()->create(['country_of_residence' => 'CH']);

    Livewire::test(AdminExternalUserEditor::class)
        ->call('open', $externalUser->id)
        ->set('email', 'taken@example.test')
        ->set('zipCode', '12345')
        ->call('save')
        ->assertHasErrors([
            'email' => 'unique',
            'zipCode',
        ]);
});

it('renders external user editor actions in athlete and donor tables', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();
    ExternalUser::factory()->asDonor($event)->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee('open-external-user-editor', false)
        ->assertSee($athlete->first_name);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->assertSee('open-external-user-editor', false);
});

it('rejects unauthenticated external user editor mutations', function (): void {
    auth('web')->logout();

    Livewire::test(AdminExternalUserEditor::class)
        ->call('open', 1)
        ->assertForbidden();
});
