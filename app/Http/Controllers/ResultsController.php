<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DonationEvent;
use Illuminate\Contracts\View\View;

class ResultsController extends Controller
{
    public function show(DonationEvent $donationEvent): View
    {
        abort_unless($donationEvent->is_published, 404);

        return view('pages.results', ['resultsEvent' => $donationEvent]);
    }
}
