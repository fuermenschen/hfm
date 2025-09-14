<?php

use App\Components\AdminDonatorTable;
use App\Models\Donator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('downloads donor invoice letter pdf via component action', function (): void {
    Storage::fake('local');

    // Seed donor with stored pdf
    $path = 'webling/to-download.pdf';
    Storage::disk('local')->put($path, 'pdf');

    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'webling_data' => [
            'debitor_id' => 123,
            'letter_pdf' => [
                'disk' => 'local',
                'path' => $path,
                'size' => 3,
            ],
        ],
    ]);

    Livewire::test(AdminDonatorTable::class)
        ->call('downloadDonorInvoice', $donator->id)
        ->assertStatus(200);
});
