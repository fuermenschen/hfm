<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DonorEventInvoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Backfill partner IDs in legacy donor invoice snapshots')]
#[Signature('invoices:backfill-partner-ids')]
class BackfillInvoicePartnerIdsCommand extends Command
{
    public function handle(): int
    {
        $counts = ['updated' => 0, 'resolved' => 0, 'equal_split' => 0, 'already_structured' => 0, 'unresolved' => 0];
        $equalSplitName = __('app.equal_split');

        foreach (DonorEventInvoice::query()
            ->whereNotNull('source_snapshot')
            ->with('donationEvent.partners')
            ->lazyById(100) as $invoice) {
            $snapshot = $invoice->source_snapshot;
            if (! is_array($snapshot) || ! is_array($snapshot['lines'] ?? null) || ! array_is_list($snapshot['lines'])) {
                continue;
            }

            $eventPartners = $invoice->donationEvent->partners;
            $eventPartnerIdsByName = $eventPartners->pluck('id', 'name');
            $allocationPartnerIds = $eventPartners->sortBy('id')->pluck('id')->all();
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
                if ($partnerName === $equalSplitName || (array_key_exists('partner', $line) && $partnerName === null)) {
                    $snapshot['lines'][$lineIndex]['partner_id'] = null;
                    $snapshot['lines'][$lineIndex]['partner'] = $equalSplitName;
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
}
