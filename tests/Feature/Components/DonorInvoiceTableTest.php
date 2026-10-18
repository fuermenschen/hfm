<?php

use App\Components\AdminPersonTable;
use App\Enums\DonorPaymentVerification;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\User;
use Database\Seeders\DonorInvoiceDemoSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertStringNotContainsString;

beforeEach(function (): void {
    seed(DonorInvoiceDemoSeeder::class);
    actingAs(User::factory()->create(), 'web');
});

it('filters invoice facts within the selected event', function (string $filter, array $names): void {
    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', 'rechnungs-demo')
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
    $component = Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', 'rechnungs-demo')
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
        ->and($table->invoicePaymentDate($donor))->toBe('12.10.2026');

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
    $component = Livewire::withQueryParams(['anlass' => 'rechnungs-demo', 'rechnung' => 'unpaid'])
        ->test(AdminPersonTable::class, ['role' => 'donor']);
    $component->assertSet('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->total() === 2);
    $component->set('search', 'Teilbezahlt')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->pluck('last_name')->all() === ['Teilbezahlt']);
});

it('does not show unknown or unverified cash as zero', function (): void {
    $table = Livewire::test(AdminPersonTable::class, ['role' => 'donor'])->set('eventSlug', 'rechnungs-demo')->instance();
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
    DonorEventInvoice::query()->where('webling_excess_cents', '>', 0)->update(['webling_payment_verification' => DonorPaymentVerification::Unclear]);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', 'rechnungs-demo')->set('invoiceFilter', 'excess')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->isEmpty())
        ->assertSee('Keine Spender:innen für diesen Rechnungsfilter.');
});

it('resets pagination and selection on invoice filter changes and resets filter when event changes', function (): void {
    $donor = ExternalUser::query()->where('last_name', 'Unklar')->firstOrFail();
    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', 'rechnungs-demo')->set('checkboxValues', [$donor->id])->call('gotoPage', 2)
        ->set('invoiceFilter', 'unclear')->assertSet('checkboxValues', [])->assertSet('paginators.page', 1)
        ->set('eventSlug', '')->assertSet('invoiceFilter', '')
        ->set('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->total() === 9);
});

it('does not match an invoice from a different event or soft-deleted donor', function (): void {
    $donor = ExternalUser::query()->where('last_name', 'Unklar')->firstOrFail();
    DonorEventInvoice::factory()->forExternalUser($donor)->create(['webling_debitor_id' => 42, 'webling_remaining_cents' => 100]);
    $deleted = ExternalUser::query()->where('last_name', 'Unbezahlt')->firstOrFail();
    $deleted->delete();

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', 'rechnungs-demo')->set('invoiceFilter', 'unpaid')
        ->assertViewHas('external_users', fn ($rows): bool => $rows->pluck('last_name')->all() === ['Teilbezahlt']);
});

it('seeds the local demo idempotently', function (): void {
    seed(DonorInvoiceDemoSeeder::class);

    expect(DonationEvent::query()->where('slug', 'rechnungs-demo')->count())->toBe(1)
        ->and(ExternalUser::query()->where('email', 'like', 'invoice-demo-%@example.test')->count())->toBe(10)
        ->and(DonorEventInvoice::query()->count())->toBe(8);
});
