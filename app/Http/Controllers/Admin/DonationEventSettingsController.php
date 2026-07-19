<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationEvent;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class DonationEventSettingsController extends Controller
{
    public function create(): Factory|View
    {
        return view('pages.admin.donation-event-settings');
    }

    public function edit(DonationEvent $donationEvent): Factory|View
    {
        return view('pages.admin.donation-event-settings', [
            'donationEvent' => $donationEvent,
        ]);
    }
}
