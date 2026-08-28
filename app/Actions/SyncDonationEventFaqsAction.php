<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;

class SyncDonationEventFaqsAction
{
    /**
     * @param  array<int, array{id: int, attached: bool, group: string, sort_order: int, is_published: bool}>  $faqRows
     */
    public function __invoke(DonationEvent $donationEvent, array $faqRows): void
    {
        $syncData = [];

        foreach ($faqRows as $faqRow) {
            if ($faqRow['attached']) {
                $syncData[$faqRow['id']] = [
                    'group' => $faqRow['group'],
                    'sort_order' => $faqRow['sort_order'],
                    'is_published' => $faqRow['is_published'],
                ];
            }
        }

        $donationEvent->faqs()->syncOrFail($syncData);
    }
}
