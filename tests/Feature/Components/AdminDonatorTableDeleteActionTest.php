<?php

use App\Components\AdminDonatorTable;
use App\Models\Donator;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('deletes donor invoice debitor via component action', function (): void {
    Storage::fake('local');

    // Seed donor with debitor and pdf
    $path = 'webling/to-delete.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donator $donator */
    $donator = Donator::factory()->create([
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

    Livewire::test(AdminDonatorTable::class)
        ->call('deleteDonorInvoice', $donator->id)
        ->assertStatus(200);

    $donator->refresh();
    Storage::disk('local')->assertMissing($path);
    expect(isset($donator->webling_data['debitor_id']))->toBeFalse()
        ->and(isset($donator->webling_data['letter_pdf']))->toBeFalse();
});
