<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;

class SyncDonationEventSponsorsAction
{
    /**
     * @param  array<int, array{id: int, attached: bool, size: string, contribution_text: string|null, sort_order: int, is_published: bool}>  $sponsorRows
     */
    public function __invoke(DonationEvent $donationEvent, array $sponsorRows): void
    {
        $syncData = [];

        foreach ($sponsorRows as $sponsorRow) {
            if (! $sponsorRow['attached']) {
                continue;
            }

            $syncData[$sponsorRow['id']] = [
                'size' => $sponsorRow['size'],
                'contribution_text' => trim((string) $sponsorRow['contribution_text']),
                'sort_order' => $sponsorRow['sort_order'],
                'is_published' => $sponsorRow['is_published'],
            ];
        }

        $donationEvent->sponsors()->syncOrFail($syncData);
    }
}
