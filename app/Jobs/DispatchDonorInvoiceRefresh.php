<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DonorEventInvoice;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\UniqueFor;

#[UniqueFor(86400)]
class DispatchDonorInvoiceRefresh implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $eventId = null, public int $afterId = 0, public ?int $untilId = null)
    {
        $this->onQueue('maintenance');
    }

    public function uniqueId(): string
    {
        return ($this->eventId ?? 'scheduled').':'.$this->afterId;
    }

    public function handle(): void
    {
        $untilId = $this->untilId ?? (int) DonorEventInvoice::query()->max('id');
        $invoices = DonorEventInvoice::query()
            ->where('id', '>', $this->afterId)->where('id', '<=', $untilId)
            ->whereNotNull('webling_debitor_id')->whereNull('remote_deleted_at')->whereHas('externalUser')
            ->when($this->eventId !== null,
                fn (Builder $query): Builder => $query->where('donation_event_id', $this->eventId),
                fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                    $query->whereNull('webling_synced_at')
                        ->orWhere(function (Builder $query): void {
                            $query->whereIn('webling_state', ['paid', 'writeoff'])->where('webling_synced_at', '<', today()->subDays(6));
                        })->orWhere(function (Builder $query): void {
                            $query->where(fn (Builder $query): Builder => $query->whereNull('webling_state')->orWhereNotIn('webling_state', ['paid', 'writeoff']))
                                ->where('webling_synced_at', '<', today());
                        });
                }))
            ->orderBy('id')->limit(100)->get(['id']);

        foreach ($invoices as $invoice) {
            dispatch(new RefreshDonorInvoiceStatus($invoice->id));
        }

        if ($invoices->count() === 100) {
            dispatch(new self($this->eventId, $invoices->last()->id, $untilId));
        }
    }
}
