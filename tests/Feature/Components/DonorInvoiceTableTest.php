<?php

use App\Components\AdminPersonTable;
use App\Enums\DonorPaymentVerification;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertStringNotContainsString;

function createDonorInvoiceTableScenarios(): DonationEvent
{
    $event = DonationEvent::factory()->create();
    $scenarios = [
        'Bezahlt' => 'paid', 'Mehrzahlung' => 'excess', 'Unbezahlt' => 'unpaid',
        'Teilbezahlt' => 'partial', 'Unklar' => 'unclear', 'Abschreibung' => 'writeoff',
        'Nicht abgeglichen' => 'unsynced', 'Ohne Rechnung' => 'none', 'Gelöscht' => 'deleted',
    ];

    foreach ($scenarios as $label => $scenario) {
        $donor = ExternalUser::factory()->asDonor($event)->create([
            'first_name' => 'Demo', 'last_name' => $label, 'city' => 'Winterthur',
            'country_of_residence' => 'CH', 'phone_number' => '+41791234567',
        ]);
        $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor);

        switch ($scenario) {
            case 'paid':
                $invoice->verifiedPaid()->create();

                break;
            case 'excess':
                $invoice->verifiedPaid(3500, 2600)->create();

                break;
            case 'unpaid':
                $invoice->unpaid()->create();

                break;
            case 'partial':
                $invoice->partiallyPaid()->create();

                break;
            case 'unclear':
                $invoice->paidUnclear()->create();

                break;
            case 'writeoff':
                $invoice->verifiedPaid(4600, 5000, 400)->create();

                break;
            case 'unsynced':
                $invoice->withoutSync()->create();

                break;
            case 'deleted':
                $invoice->remotelyDeleted()->create();

                break;
        }
    }

    return $event;
}

beforeEach(function (): void {
    actingAs(User::factory()->create(), 'web');
});

it('filters invoice facts within the selected event', function (string $filter, array $names): void {
    $event = createDonorInvoiceTableScenarios();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('invoiceFilter', $filter)
        ->assertViewHas('external_users', fn ($rows): bool => $rows->pluck('last_name')->sort()->values()->all() === $names);
})->with([
    'unpaid includes installments, not unclear paid invoices' => ['unpaid', ['Teilbezahlt', 'Unbezahlt']],
    'unclear' => ['unclear', ['Unklar']],
    'excess' => ['excess', ['Mehrzahlung']],
    'partial writeoff on paid invoice' => ['writeoff', ['Abschreibung']],
    'not synced' => ['unsynced', ['Nicht abgeglichen']],
    'missing includes remote deletion' => ['missing', ['Gelöscht', 'Ohne Rechnung']],
]);

it('shows original and verified amounts separately and exports those same facts', function (): void {
    $event = createDonorInvoiceTableScenarios();
    $component = Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)
        ->set('invoiceFilter', 'excess');
    $table = $component->instance();
    assertInstanceOf(AdminPersonTable::class, $table);
    $donor = ExternalUser::query()->where('last_name', 'Mehrzahlung')->firstOrFail();
    $donor->load('donorEventInvoices');

    expect($table->invoiceFinancialValue($donor, 'invoice_original'))->toContain('26.00')
        ->and($table->invoiceFinancialValue($donor, 'invoice_received'))->toContain('35.00')
        ->and($table->invoiceFinancialValue($donor, 'invoice_excess'))->toContain('9.00')
        ->and($table->invoiceFinancialValue($donor, 'invoice_writeoff'))->toContain('0.00')
        ->and($table->invoiceVerification($donor))->toBe('Verifiziert')
        ->and($table->invoicePaymentDate($donor))->toBe(now()->format('d.m.Y'));

    $response = $table->exportAll('csv');
    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();
    assertIsString($csv);
    expect($csv)->toContain('Ursprünglicher Betrag', 'Zahlungsprüfung', 'Verifiziert', 'Mehrzahlung', '26.00', '35.00', '9.00');
    foreach (['Unbezahlt', 'Teilbezahlt', 'Unklar'] as $excluded) {
        assertStringNotContainsString($excluded, $csv);
    }

    $component->set('checkboxValues', [$donor->id])->call('exportSelected', 'csv')->assertFileDownloaded();
});

it('restores a bookmarked invoice filter and combines it with donor search', function (): void {
    $event = createDonorInvoiceTableScenarios();
    $component = Livewire::withQueryParams(['anlass' => $event->slug, 'rechnung' => 'unpaid'])
        ->test(AdminPersonTable::class, ['role' => 'donor']);
    $component->assertSet('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->total() === 2);
    $component->set('search', 'Teilbezahlt')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->pluck('last_name')->all() === ['Teilbezahlt']);
});

it('does not show unknown or unverified cash as zero', function (): void {
    $event = createDonorInvoiceTableScenarios();
    $table = Livewire::test(AdminPersonTable::class, ['role' => 'donor'])->set('eventSlug', $event->slug)->instance();
    assertInstanceOf(AdminPersonTable::class, $table);
    foreach (['Unklar', 'Teilbezahlt', 'Nicht abgeglichen', 'Gelöscht', 'Ohne Rechnung'] as $name) {
        $donor = ExternalUser::query()->where('last_name', $name)->firstOrFail();
        $donor->load('donorEventInvoices');
        expect($table->invoiceFinancialValue($donor, 'invoice_received'))->toBe('—')
            ->and($table->invoiceFinancialValue($donor, 'invoice_excess'))->toBe('—')
            ->and($table->invoicePaymentDate($donor))->toBe('—');
    }
    $unsynced = ExternalUser::query()->where('last_name', 'Nicht abgeglichen')->firstOrFail();
    $unsynced->load('donorEventInvoices');
    expect($table->invoiceTotal($unsynced))->toBe('—')
        ->and($table->invoiceFinancialValue($unsynced, 'invoice_original'))->toContain('50.00');
});

it('excludes ineligible receipts from excess filter even when cached excess remains', function (): void {
    $event = createDonorInvoiceTableScenarios();
    DonorEventInvoice::query()->where('donation_event_id', $event->id)->where('webling_excess_cents', '>', 0)->update(['webling_payment_verification' => DonorPaymentVerification::Unclear]);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)->set('invoiceFilter', 'excess')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->isEmpty())
        ->assertSee('Keine Spender:innen für diesen Rechnungsfilter.');
});

it('resets pagination and selection on invoice filter changes and resets filter when event changes', function (): void {
    $event = createDonorInvoiceTableScenarios();
    $donor = ExternalUser::query()->where('last_name', 'Unklar')->firstOrFail();
    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)->set('checkboxValues', [$donor->id])->call('gotoPage', 2)
        ->set('invoiceFilter', 'unclear')->assertSet('checkboxValues', [])->assertSet('paginators.page', 1)
        ->set('eventSlug', '')->assertSet('invoiceFilter', '')
        ->set('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->total() === 9);
});

it('does not match an invoice from a different event or soft-deleted donor', function (): void {
    $event = createDonorInvoiceTableScenarios();
    $donor = ExternalUser::query()->where('last_name', 'Unklar')->firstOrFail();
    DonorEventInvoice::factory()->forExternalUser($donor)->create(['webling_debitor_id' => 42, 'webling_remaining_cents' => 100]);
    $deleted = ExternalUser::query()->where('last_name', 'Unbezahlt')->firstOrFail();
    $deleted->delete();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event->slug)->set('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->pluck('last_name')->all() === ['Teilbezahlt']);
});
