<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorEventInvoice>
 */
class DonorEventInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_user_id' => ExternalUser::factory(),
            'donation_event_id' => DonationEvent::factory(),
            'webling_debitor_id' => null,
            'webling_invoice_number' => null,
            'webling_state' => null,
            'webling_due_date' => null,
            'webling_total_cents' => null,
            'webling_remaining_cents' => null,
            'webling_synced_at' => null,
            'pdf_disk' => null,
            'pdf_path' => null,
            'invoice_sent_at' => null,
            'invoice_reminder_sent_at' => null,
            'source_snapshot' => null,
            'source_total_cents' => null,
            'remote_deleted_at' => null,
        ];
    }

    public function forEvent(DonationEvent|int $event): static
    {
        return $this->state(fn (): array => [
            'donation_event_id' => $event instanceof DonationEvent ? $event->id : $event,
        ]);
    }

    public function forExternalUser(ExternalUser|int $externalUser): static
    {
        return $this->state(fn (): array => [
            'external_user_id' => $externalUser instanceof ExternalUser ? $externalUser->id : $externalUser,
        ]);
    }
}
