<?php

use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Models\Donator;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;

it('does nothing when no debitor_id present', function (): void {
    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'webling_data' => null,
    ]);

    // Ensure disk fake
    Storage::fake('local');

    // No call should be made to deleteInvoice; don't bind a mock

    (new DeleteDonorInvoiceDebitor($donator))->handle();

    $donator->refresh();
    expect($donator->webling_data)->toBeNull();
});

it('deletes letter pdf and removes references then deletes debitor and clears id on 204', function (): void {
    Storage::fake('local');

    // Put a fake file
    $path = 'webling/test.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'webling_data' => [
            'debitor_id' => 999,
            'letter_pdf' => [
                'disk' => 'local',
                'path' => $path,
                'size' => 3,
            ],
        ],
    ]);

    // Assert file exists first
    Storage::disk('local')->assertExists($path);

    // Mock delete response 204
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('status')->andReturn(204);

    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('deleteInvoice')->once()->with(999)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $service);

    (new DeleteDonorInvoiceDebitor($donator))->handle();

    $donator->refresh();
    // File should be gone and references cleared
    Storage::disk('local')->assertMissing($path);
    expect(isset($donator->webling_data['letter_pdf']))->toBeFalse()
        ->and(isset($donator->webling_data['debitor_id']))->toBeFalse();
});

it('throws when deletion not successful and keeps debitor_id', function (): void {
    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'webling_data' => [
            'debitor_id' => 321,
        ],
    ]);

    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('status')->andReturn(500);

    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('deleteInvoice')->once()->with(321)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $service);

    expect(fn () => (new DeleteDonorInvoiceDebitor($donator))->handle())
        ->toThrow(RuntimeException::class);

    $donator->refresh();
    expect($donator->webling_data['debitor_id'] ?? null)->toBe(321);
});
