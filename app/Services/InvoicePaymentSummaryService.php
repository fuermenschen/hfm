<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DonorInvoiceStatus;
use App\Enums\DonorPaymentVerification;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InvoicePaymentSummaryService
{
    public function __construct(private DonorInvoiceService $invoices, private DonationService $donations) {}

    /**
     * @param  Collection<int, Donation>  $donations
     * @return array{received_cents:int,excess_cents:int,writeoff_cents:int,outstanding_cents:int,unallocated_cents:int,breakdown_missing:int,never_synced:int,verified_receipts:int,unclear_payments:int,created_total:int,sent_total:int,counts:array<string,int>,partners:list<array{name:string,amount_cents:int}>}
     */
    public function summarize(?DonationEvent $event, Collection $donations): array
    {
        $partners = Partner::query()->orderBy('id')->get()->keyBy('id');
        $activeDonors = ExternalUser::query()->whereIn('id', $donations->pluck('donor_external_user_id'))->pluck('id')->flip();
        $donationsByInvoice = $donations->filter(fn (Donation $donation): bool => $activeDonors->has($donation->donor_external_user_id))
            ->groupBy(fn (Donation $donation): string => $donation->athleteRegistration->donation_event_id.':'.$donation->donor_external_user_id);
        $rows = DonorEventInvoice::query()->whereHas('externalUser')
            ->when($event instanceof DonationEvent, fn (Builder $query): Builder => $query->where('donation_event_id', $event->id))
            ->with('donationEvent.partners')->get();
        $summary = [
            'received_cents' => 0, 'excess_cents' => 0, 'writeoff_cents' => 0,
            'outstanding_cents' => 0, 'unallocated_cents' => 0, 'breakdown_missing' => 0,
            'never_synced' => 0, 'verified_receipts' => 0, 'unclear_payments' => 0,
            'created_total' => 0, 'sent_total' => 0,
            'counts' => array_fill_keys(array_column(DonorInvoiceStatus::cases(), 'value'), 0),
            'partners' => [],
        ];
        $partnerAmounts = [];

        foreach ($rows as $invoice) {
            $summary['counts'][$this->invoices->status($invoice)->value]++;
            if ($invoice->remote_deleted_at !== null || $invoice->webling_debitor_id === null) {
                continue;
            }

            $summary['created_total']++;
            $summary['sent_total'] += $invoice->invoice_sent_at !== null ? 1 : 0;
            $summary['never_synced'] += $invoice->webling_synced_at === null ? 1 : 0;
            $summary['outstanding_cents'] += max(0, $invoice->webling_remaining_cents ?? 0);
            $summary['writeoff_cents'] += $invoice->webling_writeoff_cents ?? 0;
            $summary['unclear_payments'] += $invoice->webling_payment_verification === DonorPaymentVerification::Unclear ? 1 : 0;
            $verified = $this->invoices->isReceiptEligible($invoice);
            $summary['breakdown_missing'] += ($invoice->webling_writeoff_cents === null
                || ($verified && $invoice->webling_excess_cents === null)) ? 1 : 0;

            if (! $verified) {
                continue;
            }

            $received = $invoice->webling_received_cents ?? 0;
            $summary['received_cents'] += $received;
            $summary['excess_cents'] += $invoice->webling_excess_cents ?? 0;
            $summary['verified_receipts']++;
            $lines = $invoice->source_snapshot['lines'] ?? null;
            if (! is_array($lines) || $lines === []) {
                $lines = $donationsByInvoice->get($invoice->donation_event_id.':'.$invoice->external_user_id, collect())
                    ->map(fn (Donation $donation): array => [
                        'partner_id' => $donation->athleteRegistration->partner_id,
                        'partner' => $donation->athleteRegistration->partner->name ?? null,
                        'total_cents' => (int) round($this->donations->calculateActualAmount($donation) * 100),
                    ])->all();
            }

            $eventPartners = $invoice->donationEvent->partners;
            $eventPartnersByName = $eventPartners->keyBy('name');
            $equalPartnerIds = $invoice->source_snapshot['allocation_partner_ids'] ?? $eventPartners
                ->reject(fn (Partner $partner): bool => $partner->name === 'alle zu gleichen Teilen')
                ->pluck('id')->all();
            $equalPartnerIds = array_values(array_unique(array_filter(
                $equalPartnerIds,
                fn (mixed $partnerId): bool => is_int($partnerId)
                    && ($partners->get($partnerId)->name ?? null) !== 'alle zu gleichen Teilen',
            )));
            $directWeightMultiplier = max(1, count($equalPartnerIds));
            $weights = [];
            foreach ($lines as $line) {
                if (! is_array($line) || ! is_int($line['total_cents'] ?? null) || $line['total_cents'] <= 0) {
                    continue;
                }

                $name = $line['partner'] ?? null;
                if (array_key_exists('partner_id', $line)) {
                    $partnerId = $line['partner_id'];
                    $equalSplit = $partnerId === null
                        || (is_int($partnerId) && ($partners->get($partnerId)->name ?? null) === 'alle zu gleichen Teilen');
                } else {
                    $equalSplit = is_string($name) && in_array($name, [__('app.equal_split'), 'alle zu gleichen Teilen'], true);
                    $matchedPartner = is_string($name) ? $eventPartnersByName->get($name) : null;
                    $partnerId = $equalSplit || ! ($matchedPartner instanceof Partner) ? null : $matchedPartner->id;
                }

                if ($equalSplit) {
                    if ($equalPartnerIds === []) {
                        $weights[0] = ($weights[0] ?? 0) + $line['total_cents'];

                        continue;
                    }

                    foreach ($equalPartnerIds as $equalPartnerId) {
                        $allocationId = $partners->has($equalPartnerId) ? $equalPartnerId : 0;
                        $weights[$allocationId] = ($weights[$allocationId] ?? 0) + $line['total_cents'];
                    }

                    continue;
                }

                $partnerId = is_int($partnerId) && $partners->has($partnerId)
                    && $partners->get($partnerId)->name !== 'alle zu gleichen Teilen' ? $partnerId : 0;
                $weights[$partnerId] = ($weights[$partnerId] ?? 0) + $line['total_cents'] * $directWeightMultiplier;
            }

            foreach ($this->allocate($received, array_sum($weights) > 0 ? $weights : [0 => 1]) as $id => $amount) {
                $partnerAmounts[$id] = ($partnerAmounts[$id] ?? 0) + $amount;
            }
        }

        $existingPairs = $rows->mapWithKeys(fn (DonorEventInvoice $invoice): array => [$invoice->donation_event_id.':'.$invoice->external_user_id => true]);
        $summary['counts']['not_created'] += $donationsByInvoice->keys()->diff($existingPairs->keys())->count();
        $summary['unallocated_cents'] = $partnerAmounts[0] ?? 0;
        $eventPartnerIds = $event instanceof DonationEvent ? $event->partners()->pluck('partners.id') : $partners->keys();
        foreach ($partners as $partner) {
            if ($partner->name !== 'alle zu gleichen Teilen' && ($eventPartnerIds->contains($partner->id) || ($partnerAmounts[$partner->id] ?? 0) > 0)) {
                $summary['partners'][] = ['name' => $partner->name, 'amount_cents' => $partnerAmounts[$partner->id] ?? 0];
            }
        }

        return $summary;
    }

    /**
     * Largest remainders preserve every cent; partner ID breaks ties consistently.
     *
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    protected function allocate(int $amount, array $weights): array
    {
        ksort($weights);
        $total = array_sum($weights);
        $shares = [];
        $remainders = [];
        foreach ($weights as $id => $weight) {
            $shares[$id] = intdiv($amount * $weight, $total);
            $remainders[$id] = ($amount * $weight) % $total;
        }

        arsort($remainders);
        $remaining = $amount - array_sum($shares);
        foreach (array_keys($remainders) as $id) {
            if ($remaining-- <= 0) {
                break;
            }

            $shares[$id]++;
        }

        return $shares;
    }
}
