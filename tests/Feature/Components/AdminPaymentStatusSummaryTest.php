<?php

use App\Components\AdminPaymentStatusSummary;
use Livewire\Livewire;

it('maps all nine status buckets from the summary event', function (): void {
    Livewire::test(AdminPaymentStatusSummary::class)
        ->dispatch('showPaymentStatusSummary', summary: [
            'not_created' => 1,
            'created' => 2,
            'sent' => 3,
            'overdue' => 4,
            'partially_paid' => 5,
            'paid' => 6,
            'writeoff' => 7,
            'remote_deleted' => 8,
            'unknown' => 9,
        ])
        ->assertSet('notCreated', 1)
        ->assertSet('created', 2)
        ->assertSet('sent', 3)
        ->assertSet('overdue', 4)
        ->assertSet('partiallyPaid', 5)
        ->assertSet('paid', 6)
        ->assertSet('writeoff', 7)
        ->assertSet('remoteDeleted', 8)
        ->assertSet('unknown', 9);
});

it('falls back to zero for buckets missing from legacy payloads', function (): void {
    Livewire::test(AdminPaymentStatusSummary::class)
        ->dispatch('showPaymentStatusSummary', summary: [
            'paid' => 2,
            'overdue' => 1,
            'sent' => 3,
            'created' => 4,
            'not_created' => 5,
        ])
        ->assertSet('paid', 2)
        ->assertSet('overdue', 1)
        ->assertSet('sent', 3)
        ->assertSet('created', 4)
        ->assertSet('notCreated', 5)
        ->assertSet('partiallyPaid', 0)
        ->assertSet('writeoff', 0)
        ->assertSet('remoteDeleted', 0)
        ->assertSet('unknown', 0);
});
