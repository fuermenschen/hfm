<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationEvent;
use App\Settings\EventSettings;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class DonationEventSettingsController extends Controller
{
    public function create(): Factory|View
    {
        return view('pages.admin.donation-event-settings');
    }

    public function edit(DonationEvent $donationEvent, EventSettings $eventSettings): Factory|View
    {
        return view('pages.admin.donation-event-settings', [
            'donationEvent' => $donationEvent,
            'isCurrentEvent' => $eventSettings->current_event_id === $donationEvent->id,
        ]);
    }
}
