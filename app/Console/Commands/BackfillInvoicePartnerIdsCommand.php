<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DonorEventInvoice;
use App\Models\Partner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Description('Backfill partner IDs in legacy donor invoice snapshots')]
#[Signature('invoices:backfill-partner-ids')]
class BackfillInvoicePartnerIdsCommand extends Command
{
    public function handle(): int
    {
        $counts = ['updated' => 0, 'resolved' => 0, 'equal_split' => 0, 'already_structured' => 0, 'unresolved' => 0];
        $equalSplitNames = array_unique([__('app.equal_split'), 'alle zu gleichen Teilen']);

        foreach (DonorEventInvoice::query()
            ->whereNotNull('source_snapshot')
            ->with('donationEvent.partners')
            ->lazyById(100) as $invoice) {
            $snapshot = $invoice->source_snapshot;
            if (! is_array($snapshot) || ! is_array($snapshot['lines'] ?? null) || ! array_is_list($snapshot['lines'])) {
                continue;
            }

            $eventPartners = $invoice->donationEvent->partners;
            $eventPartnerIdsByName = $this->eventPartnerIdsByName($eventPartners, $equalSplitNames);
            $allocationPartnerIds = $this->allocationPartnerIds($eventPartners, $equalSplitNames);
            $changed = false;
            $hasEqualSplit = false;

            foreach ($snapshot['lines'] as $lineIndex => $line) {
                if (! is_array($line)) {
                    $counts['unresolved']++;

                    continue;
                }

                if (array_key_exists('partner_id', $line)) {
                    if ($line['partner_id'] === null || is_int($line['partner_id'])) {
                        $counts['already_structured']++;
                        $hasEqualSplit = $hasEqualSplit || $line['partner_id'] === null;
                    } else {
                        $counts['unresolved']++;
                    }

                    continue;
                }

                $partnerName = $line['partner'] ?? null;
                if (is_string($partnerName) && in_array($partnerName, $equalSplitNames, true)) {
                    $snapshot['lines'][$lineIndex]['partner_id'] = null;
                    $counts['equal_split']++;
                    $hasEqualSplit = true;
                    $changed = true;

                    continue;
                }

                if (is_string($partnerName) && $eventPartnerIdsByName->has($partnerName)) {
                    $snapshot['lines'][$lineIndex]['partner_id'] = $eventPartnerIdsByName->get($partnerName);
                    $counts['resolved']++;
                    $changed = true;

                    continue;
                }

                $counts['unresolved']++;
            }

            if ($hasEqualSplit && ! array_key_exists('allocation_partner_ids', $snapshot)) {
                $snapshot['allocation_partner_ids'] = $allocationPartnerIds;
                $changed = true;
            }

            if ($changed) {
                $invoice->forceFill(['source_snapshot' => $snapshot])->save();
                $counts['updated']++;
            }
        }

        $this->info('Invoice partner snapshot backfill complete.');
        $this->line('Invoices updated: '.$counts['updated']);
        $this->line('Partner IDs resolved: '.$counts['resolved']);
        $this->line('Equal-split lines normalized: '.$counts['equal_split']);
        $this->line('Already structured lines: '.$counts['already_structured']);
        $this->line('Unresolved legacy lines: '.$counts['unresolved']);

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Partner>  $eventPartners
     * @param  list<string>  $equalSplitNames
     * @return Collection<string, int<0, max>>
     */
    protected function eventPartnerIdsByName(Collection $eventPartners, array $equalSplitNames): Collection
    {
        return $eventPartners
            ->reject(fn (Partner $partner): bool => in_array($partner->name, $equalSplitNames, true))
            ->mapWithKeys(fn (Partner $partner): array => [$partner->name => $partner->id]);
    }

    /**
     * @param  Collection<int, Partner>  $eventPartners
     * @param  list<string>  $equalSplitNames
     * @return list<int>
     */
    protected function allocationPartnerIds(Collection $eventPartners, array $equalSplitNames): array
    {
        return $eventPartners
            ->reject(fn (Partner $partner): bool => in_array($partner->name, $equalSplitNames, true))
            ->sortBy('id')
            ->pluck('id')
            ->all();
    }
}
