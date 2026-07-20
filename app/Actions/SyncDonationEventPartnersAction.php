<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SyncDonationEventPartnersAction
{
    /**
     * @param  array<int, array{id: int, attached: bool, sort_order: int, is_published: bool}>  $partnerRows
     */
    public function __invoke(DonationEvent $donationEvent, array $partnerRows): void
    {
        $rowsByPartnerId = [];
        $syncData = [];

        foreach ($partnerRows as $partnerRow) {
            $partnerId = $partnerRow['id'];
            $rowsByPartnerId[$partnerId] = $partnerRow;

            if ($partnerRow['attached']) {
                $syncData[$partnerId] = $this->pivotData($partnerRow);
            }
        }

        $referencedPartnerIds = AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->whereNotNull('partner_id')
            ->distinct()
            ->pluck('partner_id');

        $existingReferencedPartners = $donationEvent->partners()
            ->whereKey($referencedPartnerIds)
            ->get()
            ->keyBy('id');

        foreach ($referencedPartnerIds as $referencedPartnerId) {
            $partnerId = (int) $referencedPartnerId;

            if (
                isset($rowsByPartnerId[$partnerId])
                && array_key_exists('sort_order', $rowsByPartnerId[$partnerId])
                && array_key_exists('is_published', $rowsByPartnerId[$partnerId])
            ) {
                $syncData[$partnerId] = $this->pivotData($rowsByPartnerId[$partnerId]);

                continue;
            }

            $existingPartner = $existingReferencedPartners->get($partnerId);
            $pivot = $existingPartner instanceof Partner ? $existingPartner->getRelation('pivot') : null;
            $syncData[$partnerId] = [
                'sort_order' => (int) ($pivot instanceof Pivot ? $pivot->getAttribute('sort_order') : 0),
                'is_published' => (bool) ($pivot instanceof Pivot ? $pivot->getAttribute('is_published') : false),
            ];
        }

        $donationEvent->partners()->syncOrFail($syncData);
    }

    /**
     * @param  array{id: int, attached: bool, sort_order: int, is_published: bool}  $partnerRow
     * @return array{sort_order: int, is_published: bool}
     */
    protected function pivotData(array $partnerRow): array
    {
        return [
            'sort_order' => $partnerRow['sort_order'],
            'is_published' => $partnerRow['is_published'],
        ];
    }
}
