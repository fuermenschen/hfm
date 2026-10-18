<?php

use App\Components\AdminDashboardContent;
use App\Enums\DonorPaymentVerification;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\User;
use Livewire\Livewire;

it('keeps deferred dashboard stats behind the admin guard', function (): void {
    $event = DonationEvent::factory()->create();

    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
        ->assertForbidden();
});

it('labels verified cash with unresolved beneficiary shares as unclear partner allocation', function (): void {
    $event = DonationEvent::factory()->create();
    DonorEventInvoice::factory()->forEvent($event)->create([
        'webling_debitor_id' => 42, 'webling_state' => 'paid', 'webling_remaining_cents' => 0,
        'webling_received_cents' => 52385, 'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_payment_date' => '2026-10-12', 'webling_synced_at' => now(),
        'source_snapshot' => ['lines' => [['partner' => 'Missing beneficiary', 'total_cents' => 52385]]],
    ]);

    Livewire::actingAs(User::factory()->create(), 'web')
        ->test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
        ->assertSee('Unklare Partnerzuordnung')
        ->assertSee('523.85')
        ->assertDontSee('Noch nicht zugeordnet');
});

it('distinguishes computed donations, confirmed pledges and verified invoice receipts with optional help', function (): void {
    Livewire::actingAs(User::factory()->create(), 'web')
        ->test(AdminDashboardContent::class)
        ->assertSee('Entwicklung rund um den Anlass')
        ->assertSee('Spenden (berechnet)')
        ->assertSee('Berechnete Spenden')
        ->assertSee('Geschätzte Runden Ø')
        ->assertSee('Bestätigt')
        ->assertSee('Zahlung verifiziert')
        ->assertSee('Zahlung unklar')
        ->assertSee('Hinweis zu Zahlung verifiziert')
        ->assertSee('Mehrere Zahlungen für dieselbe Rechnung zählen einmal.')
        ->assertSee('nicht den Zahlungseingang.')
        ->assertSee('Kein Zahlungseingang.')
        ->assertSee('ursprünglichen HFM-Rechnungsbetrag')
        ->assertSee('Teilabschreibungen auf bezahlten Rechnungen')
        ->assertSee('Teilzahlungen werden berücksichtigt')
        ->assertDontSee('Spenden (tatsächlich)')
        ->assertDontSee('Tatsächliche Spenden')
        ->assertDontSee('Unklare Zahlungseingänge');
});
