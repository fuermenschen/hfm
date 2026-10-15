<?php

use App\Components\AdminDashboardContent;
use App\Models\DonationEvent;
use Livewire\Livewire;

it('keeps deferred dashboard stats behind the admin guard', function (): void {
    $event = DonationEvent::factory()->create();

    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
        ->assertForbidden();
});
