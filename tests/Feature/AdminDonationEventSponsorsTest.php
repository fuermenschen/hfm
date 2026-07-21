<?php

use App\Components\AdminDonationEventSponsorsForm;
use App\Models\DonationEvent;
use App\Models\Sponsor;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('shows sponsor tab on the event edit page', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    actingAs(User::factory()->create());

    get(route('admin.donation-events.edit', $donationEvent))
        ->assertSuccessful()
        ->assertSee('Sponsor:innen')
        ->assertSee('Zugeordnete Sponsor:innen')
        ->assertSee('Verfügbare Sponsor:innen')
        ->assertSee('Zuordnungen');
});

it('loads sponsor assignments with pivot values', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $assignedSponsor = Sponsor::factory()->create(['name' => 'Alpha Sponsor']);
    Sponsor::factory()->create(['name' => 'Beta Sponsor']);
    $donationEvent->sponsors()->attach($assignedSponsor, [
        'size' => 'large',
        'contribution_text' => 'Finanziert die Verpflegung.',
        'sort_order' => 20,
        'is_published' => false,
    ]);

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('sponsorRows.0.name', 'Alpha Sponsor')
        ->assertSet('sponsorRows.0.attached', true)
        ->assertSet('sponsorRows.0.size', 'large')
        ->assertSet('sponsorRows.0.contribution_text', 'Finanziert die Verpflegung.')
        ->assertSet('sponsorRows.0.sort_order', 20)
        ->assertSet('sponsorRows.0.is_published', false)
        ->assertSet('sponsorRows.1.name', 'Beta Sponsor')
        ->assertSet('sponsorRows.1.attached', false)
        ->assertSet('sponsorRows.1.size', 'medium')
        ->assertSet('sponsorRows.1.is_published', false)
        ->assertSee('x-bind:disabled="!$wire.sponsorRows[0].attached"', escape: false)
        ->assertSee('Vom Anlass entfernen');
});

it('attaches updates and detaches sponsors', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $detachedSponsor = Sponsor::factory()->create(['name' => 'Alpha Sponsor']);
    $attachedSponsor = Sponsor::factory()->create(['name' => 'Beta Sponsor']);
    $donationEvent->sponsors()->attach($detachedSponsor, [
        'size' => 'small',
        'contribution_text' => 'Alter Beitrag',
        'sort_order' => 10,
        'is_published' => true,
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => $donationEvent])
        ->set('sponsorRows.0.attached', false)
        ->set('sponsorRows.1.attached', true)
        ->set('sponsorRows.1.size', 'large')
        ->set('sponsorRows.1.contribution_text', '  Neuer Anlassbeitrag  ')
        ->set('sponsorRows.1.sort_order', 5)
        ->set('sponsorRows.1.is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseMissing('donation_event_sponsor', [
        'donation_event_id' => $donationEvent->id,
        'sponsor_id' => $detachedSponsor->id,
    ]);
    assertDatabaseHas('donation_event_sponsor', [
        'donation_event_id' => $donationEvent->id,
        'sponsor_id' => $attachedSponsor->id,
        'size' => 'large',
        'contribution_text' => 'Neuer Anlassbeitrag',
        'sort_order' => 5,
        'is_published' => false,
    ]);
});

it('undoes a new sponsor assignment before saving', function (): void {
    Sponsor::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->call('attachSponsor', 0)
        ->assertSet('sponsorRows.0.attached', true)
        ->assertSee('Zuordnung rückgängig')
        ->call('detachSponsor', 0)
        ->assertSet('sponsorRows.0.attached', false)
        ->assertDontSee('Zuordnung rückgängig');
});

it('requires valid pivot data for attached sponsors', function (): void {
    Sponsor::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->set('sponsorRows.0.attached', true)
        ->set('sponsorRows.0.size', 'extra-large')
        ->set('sponsorRows.0.contribution_text', '   ')
        ->set('sponsorRows.0.sort_order', -1)
        ->call('save')
        ->assertHasErrors([
            'sponsorRows.0.size' => 'in',
            'sponsorRows.0.contribution_text' => 'required',
            'sponsorRows.0.sort_order' => 'min',
        ]);
});

it('ignores invalid pivot data for detached sponsors', function (): void {
    Sponsor::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->set('sponsorRows.0.size', 'extra-large')
        ->set('sponsorRows.0.sort_order', -1)
        ->call('save')
        ->assertHasNoErrors();
});

it('loads assigned sponsors in public order and available sponsors alphabetically', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $laterByName = Sponsor::factory()->create(['name' => 'Zulu Assigned']);
    $firstByName = Sponsor::factory()->create(['name' => 'Alpha Assigned']);
    Sponsor::factory()->create(['name' => 'Beta Available']);
    Sponsor::factory()->create(['name' => 'Alpha Available']);

    $pivot = ['size' => 'medium', 'contribution_text' => 'Beitrag', 'is_published' => true];
    $donationEvent->sponsors()->attach($laterByName, [...$pivot, 'sort_order' => 10]);
    $donationEvent->sponsors()->attach($firstByName, [...$pivot, 'sort_order' => 20]);

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('sponsorRows.0.name', 'Zulu Assigned')
        ->assertSet('sponsorRows.1.name', 'Alpha Assigned')
        ->assertSet('sponsorRows.2.name', 'Alpha Available')
        ->assertSet('sponsorRows.3.name', 'Beta Available');
});

it('allows detached sponsors without contribution text', function (): void {
    Sponsor::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->assertSet('sponsorRows.0.attached', false)
        ->assertSet('sponsorRows.0.contribution_text', '')
        ->call('save')
        ->assertHasNoErrors();
});

it('validates duplicate sponsor rows', function (): void {
    Sponsor::factory()->count(2)->create();

    actingAs(User::factory()->create());

    $component = Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()]);
    $sponsorRows = $component->get('sponsorRows');

    $component
        ->set('sponsorRows.1.id', $sponsorRows[0]['id'])
        ->call('save')
        ->assertHasErrors(['sponsorRows.1.id' => 'distinct']);
});

it('rejects unauthenticated sponsor mutations', function (): void {
    Livewire::test(AdminDonationEventSponsorsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->call('save')
        ->assertForbidden();
});
