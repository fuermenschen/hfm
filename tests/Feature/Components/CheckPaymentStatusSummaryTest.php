<?php

use App\Components\AdminDonorTable;
use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Livewire\Livewire;

it('dispatches summary modal event with correct counts after checking payment status', function (): void {
    // Seed donors across all buckets
    Donor::factory()->create(['webling_data' => ['payment_status' => 'paid']]);
    Donor::factory()->create(['webling_data' => ['payment_status' => 'overdue']]);
    Donor::factory()->create(['invoice_sent_at' => now(), 'webling_data' => []]);
    Donor::factory()->create(['webling_data' => ['letter_pdf' => ['disk' => 'local', 'path' => 'invoices/sample.pdf']]]);
    Donor::factory()->create(['webling_data' => []]);

    // Mock Webling service used by the job so no changes are applied
    $service = Mockery::mock(WeblingInvoiceService::class);
    $resp = Mockery::mock(Response::class);
    $resp->shouldReceive('json')->andReturn([]);
    // The job will call index twice; return empty on both
    $service->shouldReceive('index')->andReturn($resp);
    app()->instance(WeblingInvoiceService::class, $service);

    Livewire::test(AdminDonorTable::class)
        ->call('checkPaymentStatus')
        ->assertDispatched('showPaymentStatusSummary', function ($event, $params) {
            return ($params[0]['paid'] ?? null) === 1
                && ($params[0]['overdue'] ?? null) === 1
                && ($params[0]['sent'] ?? null) === 1
                && ($params[0]['created'] ?? null) === 1
                && ($params[0]['not_created'] ?? null) === 1;
        });
});
