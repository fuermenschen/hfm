<?php

use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;

it('does nothing when no debitor_id present', function (): void {
    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => null,
    ]);

    // Ensure disk fake
    Storage::fake('local');

    // No call should be made to deleteInvoice; don't bind a mock

    (new DeleteDonorInvoiceDebitor($donor))->handle();

    $donor->refresh();
    expect($donor->webling_data)->toBeNull();
});

it('deletes letter pdf and removes references then deletes debitor and clears id on 204', function (): void {
    Storage::fake('local');

    // Put a fake file
    $path = 'webling/test.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donor $donor */
    $donor = Donor::factory()->create([
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

    (new DeleteDonorInvoiceDebitor($donor))->handle();

    $donor->refresh();
    // File should be gone and references cleared
    Storage::disk('local')->assertMissing($path);
    expect(isset($donor->webling_data['letter_pdf']))->toBeFalse()
        ->and(isset($donor->webling_data['debitor_id']))->toBeFalse();
});

it('throws when deletion not successful and keeps debitor_id', function (): void {
    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [
            'debitor_id' => 321,
        ],
    ]);

    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('status')->andReturn(500);

    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('deleteInvoice')->once()->with(321)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $service);

    expect(fn () => (new DeleteDonorInvoiceDebitor($donor))->handle())
        ->toThrow(RuntimeException::class);

    $donor->refresh();
    expect($donor->webling_data['debitor_id'] ?? null)->toBe(321);
});

it('deletes letter pdf even if no debitor_id is present', function (): void {
    Storage::fake('local');

    // Put a fake file
    $path = 'webling/orphan.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [
            'letter_pdf' => [
                'disk' => 'local',
                'path' => $path,
                'size' => 3,
            ],
        ],
    ]);

    // Assert file exists first
    Storage::disk('local')->assertExists($path);

    // No call should be made to deleteInvoice; don't bind a mock

    (new DeleteDonorInvoiceDebitor($donor))->handle();

    $donor->refresh();
    // File should be gone and references cleared
    Storage::disk('local')->assertMissing($path);
    expect(isset($donor->webling_data['letter_pdf']))->toBeFalse()
        ->and(isset($donor->webling_data['debitor_id']))->toBeFalse();
});

it('treats 404 from external deletion as success and clears debitor_id', function (): void {
    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [
            'debitor_id' => 12345,
        ],
    ]);

    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('status')->andReturn(404);

    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('deleteInvoice')->once()->with(12345)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $service);

    (new DeleteDonorInvoiceDebitor($donor))->handle();

    $donor->refresh();
    expect(isset($donor->webling_data['debitor_id']))->toBeFalse();
});
