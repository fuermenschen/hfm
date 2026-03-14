<?php

use App\Jobs\CheckDonorInvoicesStatus;
use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;

it('marks donors as paid or overdue based on Webling index responses', function (): void {
    // Seed donors with known debitor_ids
    /** @var Donor $paidDonor */
    $paidDonor = Donor::factory()->create([
        'webling_data' => ['debitor_id' => 101],
    ]);
    /** @var Donor $overdueDonor */
    $overdueDonor = Donor::factory()->create([
        'webling_data' => ['debitor_id' => 202],
    ]);
    /** @var Donor $unrelatedDonor */
    $unrelatedDonor = Donor::factory()->create([
        'webling_data' => ['debitor_id' => 303],
    ]);

    // Mock the WeblingInvoiceService to return IDs
    $service = Mockery::mock(WeblingInvoiceService::class);

    $paidResponse = Mockery::mock(Response::class);
    $paidResponse->shouldReceive('json')->andReturn(['items' => [101, 99999]]);

    $overdueResponse = Mockery::mock(Response::class);
    $overdueResponse->shouldReceive('json')->andReturn([202]);

    $service->shouldReceive('index')->once()->with(['state' => 'paid'])->andReturn($paidResponse);
    $service->shouldReceive('index')->once()->with([
        ['state', '!=', 'paid'],
        ['duedate', '<', 'TODAY()'],
    ])->andReturn($overdueResponse);

    // Bind our mock
    app()->instance(WeblingInvoiceService::class, $service);

    CheckDonorInvoicesStatus::dispatchSync();

    // Refresh models
    $paidDonor->refresh();
    $overdueDonor->refresh();
    $unrelatedDonor->refresh();

    expect($paidDonor->webling_data['payment_status'] ?? null)->toBe('paid')
        ->and($overdueDonor->webling_data['payment_status'] ?? null)->toBe('overdue')
        ->and($unrelatedDonor->webling_data['payment_status'] ?? null)->toBeNull();
});
