<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DonorEventInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $external_user_id
 * @property int $donation_event_id
 * @property int|null $webling_debitor_id
 * @property string|null $webling_invoice_number
 * @property string|null $webling_state
 * @property Carbon|null $webling_due_date
 * @property int|null $webling_total_cents
 * @property int|null $webling_remaining_cents
 * @property Carbon|null $webling_synced_at
 * @property string|null $pdf_disk
 * @property string|null $pdf_path
 * @property Carbon|null $invoice_sent_at
 * @property Carbon|null $invoice_reminder_sent_at
 * @property array<string, mixed>|null $source_snapshot
 * @property int|null $source_total_cents
 * @property Carbon|null $remote_deleted_at
 * @property ExternalUser $externalUser
 * @property DonationEvent $donationEvent
 */
#[Fillable([
    'external_user_id',
    'donation_event_id',
    'webling_debitor_id',
    'webling_invoice_number',
    'webling_state',
    'webling_due_date',
    'webling_total_cents',
    'webling_remaining_cents',
    'webling_synced_at',
    'pdf_disk',
    'pdf_path',
    'invoice_sent_at',
    'invoice_reminder_sent_at',
    'source_snapshot',
    'source_total_cents',
    'remote_deleted_at',
])]
class DonorEventInvoice extends Model
{
    /** @use HasFactory<DonorEventInvoiceFactory> */
    use HasFactory;

    public function externalUser(): BelongsTo
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function donationEvent(): BelongsTo
    {
        return $this->belongsTo(DonationEvent::class);
    }

    protected function casts(): array
    {
        return [
            'external_user_id' => 'integer',
            'donation_event_id' => 'integer',
            'webling_debitor_id' => 'integer',
            'webling_due_date' => 'date',
            'webling_total_cents' => 'integer',
            'webling_remaining_cents' => 'integer',
            'webling_synced_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'invoice_reminder_sent_at' => 'datetime',
            'source_snapshot' => 'array',
            'source_total_cents' => 'integer',
            'remote_deleted_at' => 'datetime',
        ];
    }
}
