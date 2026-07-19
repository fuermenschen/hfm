<?php

use App\Actions\SyncDonationEventPartnersAction;
use App\Components\AdminDonationEventPartnersForm;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('shows event and partner tabs on the edit page', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    actingAs(User::factory()->create());

    get(route('admin.donation-events.edit', $donationEvent))
        ->assertSuccessful()
        ->assertSee('Anlass')
        ->assertSee('Partner:innen')
        ->assertSee('Partner:innen zuordnen');
});

it('loads assignments and locks partners referenced by registrations', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $assignedPartner = Partner::factory()->create(['name' => 'Alpha Partner']);
    $referencedPartner = Partner::factory()->create(['name' => 'Beta Partner']);

    $donationEvent->partners()->attach($assignedPartner, [
        'sort_order' => 20,
        'is_published' => false,
    ]);
    AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->withPartner($referencedPartner)
        ->create();

    Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('partnerRows.0.name', 'Alpha Partner')
        ->assertSet('partnerRows.0.attached', true)
        ->assertSet('partnerRows.0.sort_order', 20)
        ->assertSet('partnerRows.0.is_published', false)
        ->assertSet('partnerRows.0.is_locked', false)
        ->assertSet('partnerRows.1.name', 'Beta Partner')
        ->assertSet('partnerRows.1.attached', true)
        ->assertSet('partnerRows.1.is_locked', true)
        ->assertSet('partnerRows.1.registration_count', 1);
});

it('attaches updates and detaches unreferenced partners', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $detachedPartner = Partner::factory()->create(['name' => 'Alpha Partner']);
    $attachedPartner = Partner::factory()->create(['name' => 'Beta Partner']);

    $donationEvent->partners()->attach($detachedPartner, [
        'sort_order' => 10,
        'is_published' => true,
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => $donationEvent])
        ->set('partnerRows.0.attached', false)
        ->set('partnerRows.1.attached', true)
        ->set('partnerRows.1.sort_order', 5)
        ->set('partnerRows.1.is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseMissing('donation_event_partner', [
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $detachedPartner->id,
    ]);
    assertDatabaseHas('donation_event_partner', [
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $attachedPartner->id,
        'sort_order' => 5,
        'is_published' => false,
    ]);
});

it('keeps referenced partners attached when submitted as detached', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $donationEvent->partners()->attach($partner, [
        'sort_order' => 10,
        'is_published' => true,
    ]);
    AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->withPartner($partner)
        ->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => $donationEvent])
        ->set('partnerRows.0.attached', false)
        ->set('partnerRows.0.sort_order', 30)
        ->set('partnerRows.0.is_published', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('partnerRows.0.attached', true)
        ->assertSet('partnerRows.0.is_locked', true);

    assertDatabaseHas('donation_event_partner', [
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $partner->id,
        'sort_order' => 30,
        'is_published' => false,
    ]);
});

it('attaches partners referenced by legacy registrations without a pivot', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->withPartner($partner)
        ->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('partnerRows.0.attached', true)
        ->assertSet('partnerRows.0.is_locked', true)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('donation_event_partner', [
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $partner->id,
        'is_published' => true,
    ]);
});

it('retains referenced partners omitted from the action payload', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $donationEvent->partners()->attach($partner, [
        'sort_order' => 40,
        'is_published' => false,
    ]);
    AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->withPartner($partner)
        ->create();

    app(SyncDonationEventPartnersAction::class)($donationEvent, []);

    assertDatabaseHas('donation_event_partner', [
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $partner->id,
        'sort_order' => 40,
        'is_published' => false,
    ]);
});

it('validates partner rows', function (): void {
    Partner::factory()->count(2)->create();

    actingAs(User::factory()->create());

    $component = Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => DonationEvent::factory()->create()]);
    $partnerRows = $component->get('partnerRows');

    $component
        ->set('partnerRows.1.id', $partnerRows[0]['id'])
        ->set('partnerRows.0.sort_order', -1)
        ->call('save')
        ->assertHasErrors([
            'partnerRows.0.sort_order' => 'min',
            'partnerRows.1.id' => 'distinct',
        ]);
});

it('rejects unauthenticated partner mutations', function (): void {
    Livewire::test(AdminDonationEventPartnersForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->call('save')
        ->assertForbidden();
});
