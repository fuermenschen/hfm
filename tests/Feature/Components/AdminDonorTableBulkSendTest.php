<?php

use App\Components\AdminDonorTable;
use App\Mail\GenericMailMessage;
use App\Models\Donor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('bulk sends invoices only for eligible donors', function (): void {
    Mail::fake();
    Storage::fake('local');

    // Prepare PDF files referenced by donors
    Storage::disk('local')->put('letters/test1.pdf', 'pdf-content-1');
    Storage::disk('local')->put('letters/test2.pdf', 'pdf-content-2');

    // Eligible donors (email + existing PDF)
    $d1 = Donor::factory()->create([
        'email' => 'a@example.com',
        'webling_data' => [
            'letter_pdf' => [
                'disk' => 'local',
                'path' => 'letters/test1.pdf',
            ],
        ],
    ]);

    $d2 = Donor::factory()->create([
        'email' => 'b@example.com',
        'webling_data' => [
            'letter_pdf' => [
                'disk' => 'local',
                'path' => 'letters/test2.pdf',
            ],
        ],
    ]);

    // Missing PDF -> should be skipped
    $d3 = Donor::factory()->create([
        'email' => 'c@example.com',
        'webling_data' => [],
    ]);

    // Already sent -> should be skipped
    $d4 = Donor::factory()->create([
        'email' => 'd@example.com',
        'webling_data' => [
            'letter_pdf' => [
                'disk' => 'local',
                'path' => 'letters/test1.pdf',
            ],
        ],
        'invoice_sent_at' => now(),
    ]);

    Livewire::test(AdminDonorTable::class)
        ->set('checkboxValues', [$d1->id, $d2->id, $d3->id, $d4->id])
        ->call('bulkSendInvoice')
        ->assertStatus(200);

    // Only two mails should be queued
    Mail::assertQueued(GenericMailMessage::class, 2);

    expect($d1->fresh()->invoice_sent_at)->not()->toBeNull();
    expect($d2->fresh()->invoice_sent_at)->not()->toBeNull();
    expect($d3->fresh()->invoice_sent_at)->toBeNull();
    expect($d4->fresh()->invoice_sent_at)->not()->toBeNull();
});
