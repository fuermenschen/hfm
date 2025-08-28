<?php

use App\Components\BecomeDonatorForm;
use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(BecomeDonatorForm::class)
        ->assertStatus(200);
});

it('defaults country to CH', function () {
    Livewire::test(BecomeDonatorForm::class)
        ->assertSet('country_of_residence', 'CH');
});

it('validates ZIP per country', function () {
    // Setup required related records
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sport = SportType::query()->create(['name' => 'Run']);
    $athlete = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sport->id,
        'verified' => true,
    ]);

    // CH invalid: 5 digits
    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '80001')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Zürich')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'jane.de@example.com')
        ->set('email_confirmation', 'jane.de@example.com')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['zip_code']);

    // CH valid: 4 digits
    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Zürich')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'jane1.de@example.com')
        ->set('email_confirmation', 'jane1.de@example.com')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors(['zip_code']);

    // DE invalid: 4 digits
    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'DE')
        ->set('zip_code', '1234')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Berlin')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'jane2.de@example.com')
        ->set('email_confirmation', 'jane2.de@example.com')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['zip_code']);

    // DE valid: 5 digits (including leading zero)
    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'DE')
        ->set('zip_code', '01234')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Berlin')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'jane3.de@example.com')
        ->set('email_confirmation', 'jane3.de@example.com')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors(['zip_code']);

    // AT valid: 4 digits
    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'AT')
        ->set('zip_code', '5020')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Salzburg')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'jane4.de@example.com')
        ->set('email_confirmation', 'jane4.de@example.com')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors(['zip_code']);
});

it('persists selected country', function () {
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sport = SportType::query()->create(['name' => 'Run']);
    $athlete = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sport->id,
        'verified' => true,
    ]);

    Livewire::test(BecomeDonatorForm::class)
        ->set('country_of_residence', 'DE')
        ->set('first_name', 'Erika')
        ->set('last_name', 'Mustermann')
        ->set('address', 'Musterweg 1')
        ->set('zip_code', '10115')
        ->set('city', 'Berlin')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'erika@example.de')
        ->set('email_confirmation', 'erika@example.de')
        ->set('athlete_id', $athlete->id)
        ->set('amount_per_round', 10.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('donators', [
        'email' => 'erika@example.de',
        'country_of_residence' => 'DE',
    ]);
});
