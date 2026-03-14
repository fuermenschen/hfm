<?php

use App\Components\AdminDonorTable;
use App\Mail\GenericMailMessage;
use App\Models\Donor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('sends reminders in bulk only for eligible donors', function () {
    Storage::fake('local');
    Mail::fake();

    // Eligible donor: invoice sent, overdue, not yet reminded, has PDF
    $eligible = Donor::factory()->create();
    $eligiblePath = 'letters/rechnung_'.$eligible->id.'.pdf';
    Storage::disk('local')->put($eligiblePath, 'PDF');
    $eligible->webling_data = [
        'payment_status' => 'overdue',
        'letter_pdf' => [
            'disk' => 'local',
            'path' => $eligiblePath,
        ],
    ];
    $eligible->invoice_sent_at = now()->subDays(7);
    $eligible->save();

    // Already reminded
    $alreadyReminded = Donor::factory()->create();
    $remindedPath = 'letters/rechnung_'.$alreadyReminded->id.'.pdf';
    Storage::disk('local')->put($remindedPath, 'PDF');
    $alreadyReminded->webling_data = [
        'payment_status' => 'overdue',
        'letter_pdf' => [
            'disk' => 'local',
            'path' => $remindedPath,
        ],
    ];
    $alreadyReminded->invoice_sent_at = now()->subDays(10);
    $alreadyReminded->invoice_reminder_sent_at = now()->subDay();
    $alreadyReminded->save();

    // Not sent yet
    $notSent = Donor::factory()->create();
    $notSentPath = 'letters/rechnung_'.$notSent->id.'.pdf';
    Storage::disk('local')->put($notSentPath, 'PDF');
    $notSent->webling_data = [
        'payment_status' => 'overdue',
        'letter_pdf' => [
            'disk' => 'local',
            'path' => $notSentPath,
        ],
    ];
    $notSent->invoice_sent_at = null;
    $notSent->save();

    // Not overdue
    $notOverdue = Donor::factory()->create();
    $notOverduePath = 'letters/rechnung_'.$notOverdue->id.'.pdf';
    Storage::disk('local')->put($notOverduePath, 'PDF');
    $notOverdue->webling_data = [
        'payment_status' => 'sent',
        'letter_pdf' => [
            'disk' => 'local',
            'path' => $notOverduePath,
        ],
    ];
    $notOverdue->invoice_sent_at = now()->subDays(5);
    $notOverdue->save();

    Livewire::test(AdminDonorTable::class)
        ->set('checkboxValues', [$eligible->id, $alreadyReminded->id, $notSent->id, $notOverdue->id])
        ->call('bulkSendInvoiceReminder')
        ->assertStatus(200);

    Mail::assertQueued(GenericMailMessage::class, 1);

    expect($eligible->refresh()->invoice_reminder_sent_at)->not()->toBeNull();
    expect($alreadyReminded->refresh()->invoice_reminder_sent_at)->not()->toBeNull();
    expect($notSent->refresh()->invoice_reminder_sent_at)->toBeNull();
    expect($notOverdue->refresh()->invoice_reminder_sent_at)->toBeNull();
});
