<?php

use App\Components\AdminDonorTable;
use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('deletes donor invoice debitor via component action', function (): void {
    Storage::fake('local');

    // Seed donor with debitor and pdf
    $path = 'webling/to-delete.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [
            'debitor_id' => 456,
            'letter_pdf' => [
                'disk' => 'local',
                'path' => $path,
                'size' => 3,
            ],
        ],
    ]);

    // Mock delete call to return 204
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('status')->andReturn(204);

    $service = Mockery::mock(WeblingInvoiceService::class);
    $service->shouldReceive('deleteInvoice')->once()->with(456)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $service);

    Livewire::test(AdminDonorTable::class)
        ->call('deleteDonorInvoice', $donor->id)
        ->assertStatus(200);

    $donor->refresh();
    Storage::disk('local')->assertMissing($path);
    expect(isset($donor->webling_data['debitor_id']))->toBeFalse()
        ->and(isset($donor->webling_data['letter_pdf']))->toBeFalse();
});
