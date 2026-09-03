<?php

use App\Components\AdminPersonTable;
use App\Jobs\CreateDonorInvoice;
use App\Mail\DonorInvoiceMail;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\User;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Settings\EventSettings;
use App\Settings\WeblingApiSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function endedDonorInvoiceEvent(): DonationEvent
{
    return DonationEvent::factory()->create([
        'starts_at' => now('Europe/Zurich')->subDays(2),
        'ends_at' => now('Europe/Zurich')->subDay(),
    ]);
}

function fakeWeblingSettings(): void
{
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 321,
    ]);
}

function donorInvoiceFixture(DonationEvent $event, array $invoiceOverrides = []): array
{
    fakeWeblingSettings();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create($invoiceOverrides);

    return [$donor, $invoice];
}

function donorInvoicePdfFixture(DonationEvent $event, ExternalUser $donor, array $invoiceOverrides = []): DonorEventInvoice
{
    fakeWeblingSettings();
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create($invoiceOverrides + [
        'webling_debitor_id' => 4242,
        'source_total_cents' => 2500,
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-'.$invoice->id);

    return $invoice;
}

/**
 * @return MockInterface&WeblingInvoiceService
 */
function weblingInvoiceDetailsMock(array $details): MockInterface
{
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->andReturn($details);
    $webling->shouldReceive('debitorUrl')->andReturnUsing(
        fn (int $debitorId): string => 'https://demo.webling.ch/admin#/accounting/321/debitor/:debitor/view/'.$debitorId,
    );
    app()->instance(WeblingInvoiceService::class, $webling);

    return $webling;
}

it('renders athlete and donor tables with their role labels', function (string $role, string $label): void {
    Livewire::test(AdminPersonTable::class, ['role' => $role])
        ->assertSee('Ausgewählt: 0')
        ->assertSee($label);
})->with([
    'athletes' => ['athlete', 'Sportler:innen'],
    'donors' => ['donor', 'Spender:innen'],
]);

it('shows only people matching the selected role', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Athlete Only']);
    $donor = ExternalUser::factory()->asDonor()->create(['first_name' => 'Donor Only']);
    $both = ExternalUser::factory()->asAthlete()->asDonor()->create(['first_name' => 'Both Roles']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSee($athlete->first_name)
        ->assertSee($both->first_name)
        ->assertDontSee($donor->first_name);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->assertSee($donor->first_name)
        ->assertSee($both->first_name)
        ->assertDontSee($athlete->first_name);
});

it('searches with query builder like clauses', function (): void {
    ExternalUser::factory()->asAthlete()->create(['first_name' => 'Alpha']);
    ExternalUser::factory()->asAthlete()->create(['first_name' => 'Control']);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('search', 'Alpha')
        ->assertSee('Alpha')
        ->assertDontSee('Control');

    expect(collect($queries)->contains(
        fn (string $query): bool => str_contains($query, 'like ?'),
    ))->toBeTrue()
        ->and(collect($queries)->contains(
            fn (string $query): bool => str_contains($query, ' escape '),
        ))->toBeFalse();
});

it('filters unique athletes by event and shows their linked events', function (): void {
    $event2025 = DonationEvent::factory()->year(2025)->create();
    $event2026 = DonationEvent::factory()->year(2026)->create();
    $bothEvents = ExternalUser::factory()
        ->asAthlete($event2025)
        ->asAthlete($event2026)
        ->create(['first_name' => 'Both Events']);
    $only2025 = ExternalUser::factory()->asAthlete($event2025)->create(['first_name' => 'Only 2025']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSee($bothEvents->first_name)
        ->assertSee('2025')
        ->assertSee('2026')
        ->set('eventSlug', $event2026->slug)
        ->assertSee($bothEvents->first_name)
        ->assertDontSee($only2025->first_name);
});

it('shows selected partner and public ID for athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Test Partner']);
    $athlete = ExternalUser::factory()->asAthlete($event, [
        'partner_id' => $partner->id,
    ])->create([
        'public_id' => '4WUFNB',
    ]);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee('Benefizpartner:in')
        ->assertSee('Test Partner')
        ->assertDontSee('4WU-FNB')
        ->call('toggleColumn', 'public_id_string')
        ->assertSee('4WU-FNB');
});

it('shows equal split for athletes without a selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event, [
        'partner_id' => null,
    ])->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee(__('app.equal_split_full'));
});

it('shows selected group and confirmation pills for athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Blau']);

    $confirmedAthlete = ExternalUser::factory()->asAthlete($event, [
        'event_group_id' => $group->id,
        'verified' => true,
    ])->create(['first_name' => 'Confirmed Athlete']);
    $unconfirmedAthlete = ExternalUser::factory()->asAthlete($event, [
        'event_group_id' => $group->id,
        'verified' => false,
    ])->create(['first_name' => 'Unconfirmed Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee('Gruppe')
        ->assertSee('OK')
        ->assertSee('Team Blau')
        ->assertSee($confirmedAthlete->first_name)
        ->assertSee($unconfirmedAthlete->first_name)
        ->assertSee('NOK');
});

it('searches athletes by public ID and selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Search Partner']);
    $matchingAthlete = ExternalUser::factory()->asAthlete($event, ['partner_id' => $partner->id])->create([
        'first_name' => 'Matching Athlete',
        'public_id' => '4WUFNB',
    ]);
    $otherAthlete = ExternalUser::factory()->asAthlete($event)->create(['first_name' => 'Other Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('search', '4WUFNB')
        ->assertSee($matchingAthlete->first_name)
        ->assertDontSee($otherAthlete->first_name)
        ->set('search', 'Search Partner')
        ->assertSee($matchingAthlete->first_name)
        ->assertDontSee($otherAthlete->first_name);
});

it('sorts athletes by selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $alphaPartner = Partner::factory()->create(['name' => 'Alpha Partner']);
    $betaPartner = Partner::factory()->create(['name' => 'Beta Partner']);
    ExternalUser::factory()->asAthlete($event, ['partner_id' => $betaPartner->id])->create(['first_name' => 'Beta Athlete']);
    ExternalUser::factory()->asAthlete($event, ['partner_id' => $alphaPartner->id])->create(['first_name' => 'Alpha Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('sortBy', 'partner')
        ->assertSeeInOrder(['Alpha Partner', 'Beta Partner']);
});

it('filters donors through the athlete registration event', function (): void {
    $event2025 = DonationEvent::factory()->year(2025)->create();
    $event2026 = DonationEvent::factory()->year(2026)->create();
    $donor2025 = ExternalUser::factory()->asDonor($event2025)->create(['first_name' => 'Donor 2025']);
    $donor2026 = ExternalUser::factory()->asDonor($event2026)->create(['first_name' => 'Donor 2026']);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event2026->slug)
        ->assertSee($donor2026->first_name)
        ->assertDontSee($donor2025->first_name);
});

it('clears stale selection when an event filter changes', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('checkboxValues', [$athlete->id])
        ->set('eventSlug', $event->slug)
        ->assertSet('checkboxValues', []);
});

it('returns no people for an invalid event filter', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Visible Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', 'invalid')
        ->assertDontSee($athlete->first_name)
        ->assertSee('Keine Sportler:innen für diesen Anlass vorhanden.');
});

it('shows all people again when the event filter is cleared', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create(['first_name' => 'Visible Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('eventSlug', null)
        ->assertSee($athlete->first_name);
});

it('defaults athlete and donor tables to the current event', function (string $role): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $currentPerson = ExternalUser::factory()
        ->{$role === 'athlete' ? 'asAthlete' : 'asDonor'}($currentEvent)
        ->create(['first_name' => 'Current Person']);
    $otherPerson = ExternalUser::factory()
        ->{$role === 'athlete' ? 'asAthlete' : 'asDonor'}($otherEvent)
        ->create(['first_name' => 'Other Person']);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::test(AdminPersonTable::class, ['role' => $role])
        ->assertSet('eventSlug', $currentEvent->slug)
        ->assertSee($currentPerson->first_name)
        ->assertDontSee($otherPerson->first_name);
})->with(['athlete', 'donor']);

it('keeps an explicit event filter instead of the current event', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::withQueryParams(['anlass' => $otherEvent->slug])
        ->test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSet('eventSlug', $otherEvent->slug);
});

it('shows all people when the event filter is explicitly empty', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    ExternalUser::factory()->asAthlete($currentEvent)->create(['first_name' => 'Visible Athlete']);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::withQueryParams(['anlass' => ''])
        ->test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSet('eventSlug', '')
        ->assertSee('Visible Athlete');
});

it('explains why athlete documents require one selected event', function (): void {
    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', '')
        ->assertSee('Für Dokumente bitte genau einen Anlass auswählen.')
        ->assertSee('Willkommensbrief')
        ->assertSee('Personalisierter Flyer')
        ->assertSee('Story-Bilder')
        ->assertSee('Alle Sportler:innen')
        ->assertSee('Ausgewählte Sportler:innen')
        ->assertSee('Dokumente werden erstellt...');
});

it('downloads a flyer for an athlete in the selected event', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create([
        'first_name' => 'Peter',
        'last_name' => 'Muster',
        'public_id' => '4WUFNB',
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
        ->assertFileDownloaded('2026_Peter_M_4WU-FNB_Personalisierter_Flyer.pdf');
});

it('downloads selected athlete flyers as an event-scoped archive', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$athlete->id])
        ->call('downloadSelectedAthleteDocuments', 'personalized-flyer')
        ->assertFileDownloaded('2026_Personalisierte_Flyer.zip');
});

it('downloads all athlete flyers for the selected event', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('downloadAllAthleteDocuments', 'personalized-flyer')
        ->assertFileDownloaded('2026_Personalisierte_Flyer.zip');
});

it('downloads selected athlete Story images as an event-scoped archive', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$athlete->id])
        ->call('downloadSelectedAthleteStoryImages')
        ->assertFileDownloaded('2026_Story-Bilder.zip');
});

it('does not download athlete documents without a selected event', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', '')
        ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
        ->assertNoFileDownloaded();
});

it('does not start a second athlete document archive while one is running', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());
    $lock = Cache::lock('admin-athlete-document-download:'.auth()->id(), 600);
    expect($lock->get())->toBeTrue();

    try {
        Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
            ->set('eventSlug', $event->slug)
            ->set('checkboxValues', [$athlete->id])
            ->call('downloadSelectedAthleteDocuments', 'personalized-flyer')
            ->assertNoFileDownloaded();
    } finally {
        $lock->release();
    }
});

it('does not start a single athlete document while another is running', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());
    $lock = Cache::lock('admin-athlete-document-download:'.auth()->id(), 600);
    expect($lock->get())->toBeTrue();

    try {
        Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
            ->set('eventSlug', $event->slug)
            ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
            ->assertNoFileDownloaded();
    } finally {
        $lock->release();
    }
});

it('shows story image links for the selected athlete event', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();
    $registration = AthleteRegistration::query()
        ->where('external_user_id', $athlete->id)
        ->where('donation_event_id', $event->id)
        ->firstOrFail();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee(route('admin.story-image.download', [$registration, 'light']), false)
        ->assertSee(route('admin.story-image.download', [$registration, 'dark']), false);
});

it('downloads story images for admins and blocks external users', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();
    $registration = AthleteRegistration::query()
        ->where('external_user_id', $athlete->id)
        ->where('donation_event_id', $event->id)
        ->firstOrFail();

    actingAs(User::factory()->create());

    get(route('admin.story-image.download', [$registration, 'light']))
        ->assertDownload('story_single_light_'.$athlete->public_id_string.'.jpg');

    auth('web')->logout();
    actingAs($athlete, 'external');

    get(route('admin.story-image.download', [$registration, 'light']))
        ->assertRedirect();
});

it('shows invoice status and amounts for donors in the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    [$donor] = donorInvoiceFixture($event, [
        'webling_debitor_id' => 42,
        'webling_invoice_number' => '1542',
        'webling_state' => 'open',
        'invoice_sent_at' => now(),
        'source_total_cents' => 2500,
    ]);
    $otherDonor = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Rowless Donor']);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('toggleColumn', 'invoice_number')
        ->assertSee('Rechnung')
        ->assertSee('Gesendet')
        ->assertSee('1542')
        ->assertSee('25.00')
        ->assertSee($donor->first_name)
        ->assertSee('Nicht erstellt')
        ->assertSee($otherDonor->first_name);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertDontSee('Rechnungs-Nr.');
});

it('scopes invoice display to the selected event', function (): void {
    $eventA = DonationEvent::factory()->create();
    $eventB = DonationEvent::factory()->create();
    $donor = ExternalUser::factory()->asDonor($eventA)->asDonor($eventB)->create(['first_name' => 'Multi Donor']);
    DonorEventInvoice::factory()->forEvent($eventA)->forExternalUser($donor)->create([
        'webling_debitor_id' => 11,
        'webling_state' => 'paid',
    ]);
    DonorEventInvoice::factory()->forEvent($eventB)->forExternalUser($donor)->create([
        'webling_debitor_id' => 22,
        'webling_state' => 'partially paid',
    ]);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $eventA->slug)
        ->assertSee('Bezahlt')
        ->assertDontSee('Teilbezahlt')
        ->set('eventSlug', $eventB->slug)
        ->assertSee('Teilbezahlt')
        ->assertDontSee('Bezahlt');
});

it('explains why invoice actions require one selected event', function (): void {
    ExternalUser::factory()->asDonor()->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', '')
        ->assertSee('Für Rechnungen bitte genau einen Anlass auswählen.')
        ->assertSee('Rechnungen')
        ->assertSee('Status aktualisieren')
        ->assertSee('Zahlungsstatus');
});

it('creates donor invoices after confirmation before event end', function (): void {
    Bus::fake();
    $event = DonationEvent::factory()->create();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('confirmCreateInvoice', $donor->id)
        ->assertSet('confirmingInvoiceAction', 'create')
        ->assertSet('confirmingInvoiceUserId', $donor->id);

    Bus::assertNotDispatched(CreateDonorInvoice::class);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('confirmingInvoiceAction', 'create')
        ->set('confirmingInvoiceUserId', $donor->id)
        ->call('runConfirmedInvoiceAction');

    Bus::assertDispatched(CreateDonorInvoice::class, 1);
    expect(DonorEventInvoice::query()->where('external_user_id', $donor->id)->where('donation_event_id', $event->id)->exists())->toBeTrue();
});

it('creates donor invoices directly after event end', function (): void {
    Bus::fake();
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('confirmCreateInvoice', $donor->id)
        ->assertSet('confirmingInvoiceAction', null);

    Bus::assertDispatched(CreateDonorInvoice::class, 1);
});

it('does not create an invoice for a donor outside the selected event', function (): void {
    Bus::fake();
    $event = endedDonorInvoiceEvent();
    $otherEvent = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($otherEvent)->create();
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('confirmCreateInvoice', $donor->id);

    expect(DonorEventInvoice::query()->where('external_user_id', $donor->id)->where('donation_event_id', $event->id)->exists())->toBeFalse();
    Bus::assertNotDispatched(CreateDonorInvoice::class);
});

it('reuses the same row when recreating a deleted invoice', function (): void {
    Bus::fake();
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create([
        'remote_deleted_at' => now(),
    ]);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('confirmCreateInvoice', $donor->id);

    Bus::assertDispatched(CreateDonorInvoice::class, 1);
    expect(DonorEventInvoice::query()->sole()->id)->toBe($invoice->id);
});

it('sends donor invoices and confirms resends', function (): void {
    Mail::fake();
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = donorInvoicePdfFixture($event, $donor);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('sendInvoice', $donor->id)
        ->assertSet('confirmingInvoiceAction', null);

    Mail::assertQueued(DonorInvoiceMail::class, 1);
    expect($invoice->refresh()->invoice_sent_at)->not->toBeNull();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('sendInvoice', $donor->id)
        ->assertSet('confirmingInvoiceAction', 'send');

    Mail::assertQueued(DonorInvoiceMail::class, 1);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('confirmingInvoiceAction', 'send')
        ->set('confirmingInvoiceUserId', $donor->id)
        ->call('runConfirmedInvoiceAction');

    Mail::assertQueued(DonorInvoiceMail::class, 2);
});

it('sends reminders after live webling check', function (): void {
    Mail::fake();
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = donorInvoicePdfFixture($event, $donor, ['invoice_sent_at' => now()->subDays(3)]);
    weblingInvoiceDetailsMock([
        'state' => 'open',
        'due_date' => now()->subDay()->toDateString(),
        'invoice_number' => '99',
        'total_cents' => 2500,
        'remaining_cents' => 2500,
    ]);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('sendInvoiceReminder', $donor->id)
        ->assertSet('confirmingInvoiceAction', null);

    Mail::assertQueued(DonorInvoiceMail::class, 1);
    expect($invoice->refresh()->invoice_reminder_sent_at)->not->toBeNull();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('confirmingInvoiceAction', 'reminder')
        ->set('confirmingInvoiceUserId', $donor->id)
        ->call('runConfirmedInvoiceAction');

    Mail::assertQueued(DonorInvoiceMail::class, 2);
});

it('deletes unsettled invoices after confirmation', function (): void {
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = donorInvoicePdfFixture($event, $donor);
    $pdfPath = $invoice->pdf_path;

    $webling = weblingInvoiceDetailsMock([
        'state' => 'open',
        'due_date' => now()->addWeek()->toDateString(),
        'invoice_number' => '99',
        'total_cents' => 2500,
        'remaining_cents' => 2500,
    ]);
    $webling->shouldReceive('deleteInvoice')->once()->andReturn(
        new Response(new GuzzleHttp\Psr7\Response(204)),
    );

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('confirmDeleteInvoice', $donor->id)
        ->assertSet('confirmingInvoiceAction', 'delete')
        ->call('runConfirmedInvoiceAction');

    $invoice->refresh();
    expect($invoice->remote_deleted_at)->not->toBeNull()
        ->and($invoice->pdf_path)->toBeNull();
    Storage::disk('local')->assertMissing($pdfPath);
});

it('downloads invoice pdfs and reports missing files', function (): void {
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = donorInvoicePdfFixture($event, $donor);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('downloadInvoicePdf', $donor->id)
        ->assertFileDownloaded(sprintf('invoice_DON-%d-%d.pdf', $event->id, $donor->id));

    Storage::disk('local')->delete($invoice->pdf_path);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('downloadInvoicePdf', $donor->id)
        ->assertNoFileDownloaded();
});

it('hides send and download actions when the cached invoice pdf is missing', function (): void {
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $donor);
    $invoice = DonorEventInvoice::query()->sole();
    Storage::disk('local')->delete($invoice->pdf_path);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->assertDontSee('Rechnung herunterladen')
        ->assertDontSee('Rechnung senden');
});

it('hides delete action for paid invoices', function (): void {
    $event = endedDonorInvoiceEvent();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $donor, ['webling_state' => 'paid']);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->assertDontSee('Rechnung löschen');
});

it('links invoices to webling', function (): void {
    $event = DonationEvent::factory()->create();
    [$donor] = donorInvoiceFixture($event, ['webling_debitor_id' => 55]);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->assertSee('https://demo.webling.ch/admin#/accounting/321/debitor/:debitor/view/55', false);
});

it('bulk creates invoices for selected donors with preflight counts', function (): void {
    Bus::fake();
    $event = endedDonorInvoiceEvent();
    $withInvoice = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Existing']);
    donorInvoicePdfFixture($event, $withInvoice);
    $withoutInvoice = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Missing']);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$withInvoice->id, $withoutInvoice->id])
        ->call('confirmBulkCreateInvoices')
        ->assertSet('bulkEligibleCount', 1)
        ->assertSet('bulkSkippedCount', 1)
        ->assertSet('confirmingInvoiceAction', 'bulk_create')
        ->call('runConfirmedInvoiceAction')
        ->assertSet('checkboxValues', []);

    Bus::assertDispatched(CreateDonorInvoice::class, 1);
});

it('bulk sends invoices only for eligible donors', function (): void {
    Mail::fake();
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $unsent = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Unsent']);
    $unsentInvoice = donorInvoicePdfFixture($event, $unsent);
    $alreadySent = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Sent']);
    $sentInvoice = donorInvoicePdfFixture($event, $alreadySent, ['invoice_sent_at' => now()]);
    $withoutInvoice = ExternalUser::factory()->asDonor($event)->create(['first_name' => 'None']);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$unsent->id, $alreadySent->id, $withoutInvoice->id])
        ->call('confirmBulkSendInvoices')
        ->assertSet('bulkEligibleCount', 1)
        ->assertSet('bulkSkippedCount', 2)
        ->call('runConfirmedInvoiceAction')
        ->assertSet('checkboxValues', []);

    Mail::assertQueued(DonorInvoiceMail::class, 1);
    expect($unsentInvoice->refresh()->invoice_sent_at)->not->toBeNull()
        ->and($sentInvoice->refresh()->invoice_sent_at)->not->toBeNull();
});

it('shows only locally eligible invoices in bulk-send preflight', function (): void {
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $sendable = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $sendable);
    $paid = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $paid, ['webling_state' => 'paid']);
    $withoutEmail = ExternalUser::factory()->asDonor($event)->create(['email' => '']);
    donorInvoicePdfFixture($event, $withoutEmail);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$sendable->id, $paid->id, $withoutEmail->id])
        ->call('confirmBulkSendInvoices')
        ->assertSet('bulkEligibleCount', 1)
        ->assertSet('bulkSkippedCount', 2);
});

it('warns before bulk creation when the event has not ended', function (): void {
    $event = DonationEvent::factory()->create(['ends_at' => now('Europe/Zurich')->addDay()]);
    $donor = ExternalUser::factory()->asDonor($event)->create();
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$donor->id])
        ->call('confirmBulkCreateInvoices')
        ->assertSee('Der Anlass ist noch nicht beendet.');
});

it('bulk downloads selected invoice pdfs as a zip', function (): void {
    Storage::fake('local');
    $event = endedDonorInvoiceEvent();
    $first = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $first);
    $second = ExternalUser::factory()->asDonor($event)->create();
    donorInvoicePdfFixture($event, $second);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$first->id, $second->id])
        ->call('downloadSelectedInvoiceArchive')
        ->assertFileDownloaded($event->slug.'_rechnungen.zip');
});

it('refreshes invoice statuses only for the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $donor = ExternalUser::factory()->asDonor($event)->create();
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create(['webling_debitor_id' => 77]);
    $untrackedDonor = ExternalUser::factory()->asDonor($event)->create();
    $untrackedInvoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($untrackedDonor)->create();
    $otherDonor = ExternalUser::factory()->asDonor($otherEvent)->create();
    $otherInvoice = DonorEventInvoice::factory()->forEvent($otherEvent)->forExternalUser($otherDonor)->create(['webling_debitor_id' => 88]);

    weblingInvoiceDetailsMock([
        'state' => 'paid',
        'due_date' => null,
        'invoice_number' => '1542',
        'total_cents' => 2500,
        'remaining_cents' => 0,
    ]);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('refreshInvoiceStatuses');

    $invoice->refresh();
    $otherInvoice->refresh();
    $untrackedInvoice->refresh();
    expect($invoice->webling_state)->toBe('paid')
        ->and($invoice->webling_synced_at)->not->toBeNull()
        ->and($otherInvoice->webling_state)->toBeNull()
        ->and($otherInvoice->webling_synced_at)->toBeNull()
        ->and($untrackedInvoice->webling_state)->toBeNull()
        ->and($untrackedInvoice->webling_synced_at)->toBeNull();
});

it('dispatches payment status summary for the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $paidDonor = ExternalUser::factory()->asDonor($event)->create();
    DonorEventInvoice::factory()->forEvent($event)->forExternalUser($paidDonor)->create([
        'webling_debitor_id' => 11,
        'webling_state' => 'paid',
    ]);
    $deletedDonor = ExternalUser::factory()->asDonor($event)->create();
    DonorEventInvoice::factory()->forEvent($event)->forExternalUser($deletedDonor)->create([
        'remote_deleted_at' => now(),
    ]);
    ExternalUser::factory()->asDonor($event)->create(['first_name' => 'Rowless Donor']);
    $otherEventDonor = ExternalUser::factory()->asDonor($otherEvent)->create();
    DonorEventInvoice::factory()->forEvent($otherEvent)->forExternalUser($otherEventDonor)->create([
        'webling_debitor_id' => 33,
        'webling_state' => 'paid',
    ]);
    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->call('paymentStatusSummary')
        ->assertDispatched('showPaymentStatusSummary', function (string $eventName, array $params): bool {
            expect($eventName)->toBe('showPaymentStatusSummary')
                ->and($params['summary']['paid'])->toBe(1)
                ->and($params['summary']['remote_deleted'])->toBe(1)
                ->and($params['summary']['not_created'])->toBe(1)
                ->and($params['summary']['created'])->toBe(0);

            return true;
        });
});
