<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonorPaymentVerification;
use Carbon\Carbon;
use Database\Factories\DonorEventInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @property int $external_user_id
 * @property int $donation_event_id
 * @property int|null $webling_debitor_id
 * @property string|null $webling_invoice_number
 * @property string|null $webling_state
 * @property Carbon|null $webling_due_date
 * @property int|null $webling_total_cents
 * @property int|null $webling_remaining_cents
 * @property int|null $webling_received_cents
 * @property int|null $webling_excess_cents
 * @property int|null $webling_writeoff_cents
 * @property Carbon|null $webling_payment_date
 * @property DonorPaymentVerification $webling_payment_verification
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
    'webling_received_cents',
    'webling_excess_cents',
    'webling_writeoff_cents',
    'webling_payment_date',
    'webling_payment_verification',
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

    /**
     * Raw Webling states considered unpaid.
     */
    public const array UnpaidStates = ['open', 'partially paid'];

    /**
     * Clear invoice lifecycle fields and delete its cached PDF after commit.
     */
    public function markRemotelyDeleted(): void
    {
        $disk = $this->pdf_disk;
        $path = $this->pdf_path;

        $saved = $this->forceFill([
            'webling_debitor_id' => null,
            'webling_invoice_number' => null,
            'webling_state' => null,
            'webling_due_date' => null,
            'webling_total_cents' => null,
            'webling_remaining_cents' => null,
            'webling_received_cents' => null,
            'webling_excess_cents' => null,
            'webling_writeoff_cents' => null,
            'webling_payment_date' => null,
            'webling_payment_verification' => DonorPaymentVerification::Unverified,
            'webling_synced_at' => null,
            'pdf_disk' => null,
            'pdf_path' => null,
            'invoice_sent_at' => null,
            'invoice_reminder_sent_at' => null,
            'source_snapshot' => null,
            'source_total_cents' => null,
            'remote_deleted_at' => Date::now(),
        ])->save();
        throw_unless($saved, RuntimeException::class, 'Could not persist remote deletion of invoice ID '.$this->id.'.');

        if ($disk !== null && $path !== null) {
            $this->getConnection()->afterCommit(static function () use ($disk, $path): void {
                Storage::disk($disk)->delete($path);
            });
        }
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
            'webling_received_cents' => 'integer',
            'webling_excess_cents' => 'integer',
            'webling_writeoff_cents' => 'integer',
            'webling_payment_date' => 'date',
            'webling_payment_verification' => DonorPaymentVerification::class,
            'webling_synced_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'invoice_reminder_sent_at' => 'datetime',
            'source_snapshot' => 'array',
            'source_total_cents' => 'integer',
            'remote_deleted_at' => 'datetime',
        ];
    }
}
