<?php

use App\Jobs\CheckDonorInvoicesStatus;
use App\Services\Webling\Invoice\WeblingInvoiceService;

it('rethrows exceptions so callers can surface errors to the UI', function (): void {
    // Mock the WeblingInvoiceService to throw on the first call
    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('index')
        ->once()
        ->with(['state' => 'paid'])
        ->andThrow(new RuntimeException('webling not configured'));

    // Bind our mock so the Job receives it via container
    app()->instance(WeblingInvoiceService::class, $service);

    // When dispatching synchronously, the exception should bubble up
    CheckDonorInvoicesStatus::dispatchSync();
})->throws(RuntimeException::class);
