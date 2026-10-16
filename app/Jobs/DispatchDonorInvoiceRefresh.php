<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DonorEventInvoice;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\Date;

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
        $utcToday = Date::now('UTC')->startOfDay();
        $applicationTimezone = (string) config('app.timezone');
        $dailyCutoff = $utcToday->copy()->setTimezone($applicationTimezone);
        $weeklyCutoff = $utcToday->copy()->subDays(6)->setTimezone($applicationTimezone);
        $invoices = DonorEventInvoice::query()
            ->where('id', '>', $this->afterId)->where('id', '<=', $untilId)
            ->whereNotNull('webling_debitor_id')->whereNull('remote_deleted_at')->whereHas('externalUser')
            ->when($this->eventId !== null,
                fn (Builder $query): Builder => $query->where('donation_event_id', $this->eventId),
                fn (Builder $query): Builder => $query->where(function (Builder $query) use ($dailyCutoff, $weeklyCutoff): void {
                    $query->whereNull('webling_synced_at')
                        ->orWhere(function (Builder $query) use ($weeklyCutoff): void {
                            $query->whereIn('webling_state', ['paid', 'writeoff'])->where('webling_synced_at', '<', $weeklyCutoff);
                        })->orWhere(function (Builder $query) use ($dailyCutoff): void {
                            $query->where(fn (Builder $query): Builder => $query->whereNull('webling_state')->orWhereNotIn('webling_state', ['paid', 'writeoff']))
                                ->where('webling_synced_at', '<', $dailyCutoff);
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
