<?php

use App\Jobs\CreateDonorInvoice;
use App\Mail\GenericMailMessage;
use App\Models\Donor;
use App\Services\DonorInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('does not create donor invoice when debitor and letter pdf are already present', function (): void {
    Bus::fake();

    $donor = Donor::factory()->create([
        'webling_data' => [
            'debitor_id' => 123,
            'letter_pdf' => ['disk' => 'local', 'path' => 'letters/invoice.pdf'],
        ],
    ]);

    $result = app(DonorInvoiceService::class)->createInvoice($donor);

    expect($result['variant'])->toBe('warning')
        ->and($result['refresh'])->toBeFalse();

    Bus::assertNotDispatched(CreateDonorInvoice::class);
});

it('sends donor invoice email when donor has email and stored invoice pdf', function (): void {
    Mail::fake();
    Storage::fake('local');

    Storage::disk('local')->put('letters/invoice.pdf', 'pdf-content');

    $donor = Donor::factory()->create([
        'email' => 'service-test@example.com',
        'webling_data' => [
            'letter_pdf' => ['disk' => 'local', 'path' => 'letters/invoice.pdf'],
        ],
    ]);

    $result = app(DonorInvoiceService::class)->sendInvoice($donor);

    expect($result['variant'])->toBe('success')
        ->and($donor->fresh()?->invoice_sent_at)->not->toBeNull();

    Mail::assertQueued(GenericMailMessage::class, 1);
});

it('returns danger result when sending donor invoice without email', function (): void {
    Storage::fake('local');

    Storage::disk('local')->put('letters/invoice.pdf', 'pdf-content');

    $donor = Donor::factory()->create([
        'email' => '',
        'webling_data' => [
            'letter_pdf' => ['disk' => 'local', 'path' => 'letters/invoice.pdf'],
        ],
    ]);

    $result = app(DonorInvoiceService::class)->sendInvoice($donor);

    expect($result['variant'])->toBe('danger')
        ->and($result['heading'])->toBe('Keine E-Mail-Adresse');
});

it('builds exclusive invoice status summary', function (): void {
    Donor::factory()->create(['webling_data' => ['payment_status' => 'paid']]);
    Donor::factory()->create(['webling_data' => ['payment_status' => 'overdue']]);
    Donor::factory()->create(['invoice_sent_at' => now(), 'webling_data' => []]);
    Donor::factory()->create(['webling_data' => ['letter_pdf' => ['disk' => 'local', 'path' => 'letters/sample.pdf']]]);
    Donor::factory()->create(['webling_data' => []]);

    $summary = app(DonorInvoiceService::class)->invoiceStatusSummary();

    expect($summary)->toBe([
        'paid' => 1,
        'overdue' => 1,
        'sent' => 1,
        'created' => 1,
        'not_created' => 1,
    ]);
});
