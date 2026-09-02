<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\CreateDonorInvoice;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;

class CreateDonorInvoiceAction
{
    public function __invoke(ExternalUser $externalUser, DonationEvent $donationEvent): DonorEventInvoice
    {
        $invoice = DonorEventInvoice::query()->firstOrCreate([
            'external_user_id' => $externalUser->id,
            'donation_event_id' => $donationEvent->id,
        ]);

        dispatch(new CreateDonorInvoice($invoice));

        return $invoice;
    }
}
