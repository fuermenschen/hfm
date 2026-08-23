<?php

use App\Components\PortalProfileForm;
use App\Models\ExternalUser;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows authenticated external users their profile form', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Alte Adresse 1',
        'country_of_residence' => 'CH',
        'email' => 'francesca@example.test',
        'public_id' => 'ABC234',
    ]);

    actingAs($externalUser, 'external');

    get(route('portal.profile'))
        ->assertSuccessful()
        ->assertSeeText('Dein Profil')
        ->assertSee('Francesca')
        ->assertSee('Arslan')
        ->assertSee('francesca@example.test')
        ->assertSee('Schweiz')
        ->assertSee('ABC-234')
        ->assertSee('Alte Adresse 1');
});

it('requires external portal authentication for profile', function (): void {
    get(route('portal.profile'))->assertRedirect();
});

it('updates only residence address and phone number', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Alte Adresse 1',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.test',
    ]);

    Livewire::actingAs($externalUser, 'external')
        ->test(PortalProfileForm::class)
        ->set('address', 'Neue Adresse 2')
        ->set('zip_code', '8001')
        ->set('city', 'Zürich')
        ->set('phone_national', '78 987 65 43')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('portal.dashboard'));

    $updatedExternalUser = ExternalUser::query()->findOrFail($externalUser->id);

    expect($updatedExternalUser->address)->toBe('Neue Adresse 2')
        ->and($updatedExternalUser->zip_code)->toBe('8001')
        ->and($updatedExternalUser->city)->toBe('Zürich')
        ->and($updatedExternalUser->phone_number)->toBe('+41 78 987 65 43')
        ->and($updatedExternalUser->first_name)->toBe('Francesca')
        ->and($updatedExternalUser->last_name)->toBe('Arslan')
        ->and($updatedExternalUser->country_of_residence)->toBe('CH')
        ->and($updatedExternalUser->email)->toBe('francesca@example.test');
});

it('loads legacy national phone numbers with the locked country', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'country_of_residence' => 'CH',
        'phone_number' => '079 123 45 67',
    ]);

    Livewire::actingAs($externalUser, 'external')
        ->test(PortalProfileForm::class)
        ->assertSet('phone_national', '079 123 45 67');
});

it('validates postal code against locked country', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
    ]);

    Livewire::actingAs($externalUser, 'external')
        ->test(PortalProfileForm::class)
        ->set('zip_code', '12345')
        ->call('save')
        ->assertHasErrors('zip_code');
});

it('validates phone number against locked country', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
    ]);

    Livewire::actingAs($externalUser, 'external')
        ->test(PortalProfileForm::class)
        ->set('phone_national', 'not-a-phone-number')
        ->call('save')
        ->assertHasErrors('phone_national');
});
