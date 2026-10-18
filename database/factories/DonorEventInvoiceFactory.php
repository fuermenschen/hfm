<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DonorPaymentVerification;
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
            'webling_received_cents' => null,
            'webling_payment_date' => null,
            'webling_payment_verification' => DonorPaymentVerification::Unverified,
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

    public function verifiedPaid(int $receivedCents = 5000, ?int $sourceTotalCents = null, int $writeoffCents = 0): static
    {
        $sourceTotalCents ??= $receivedCents + $writeoffCents;
        $debitorId = fake()->unique()->numberBetween(900000, 999999);

        return $this->state(fn (): array => [
            'webling_debitor_id' => $debitorId,
            'webling_invoice_number' => (string) $debitorId,
            'webling_state' => 'paid',
            'webling_total_cents' => $receivedCents + $writeoffCents,
            'webling_remaining_cents' => 0,
            'webling_received_cents' => $receivedCents,
            'webling_payment_date' => now()->toDateString(),
            'webling_payment_verification' => DonorPaymentVerification::Verified,
            'webling_synced_at' => now(),
            'webling_excess_cents' => max(0, $receivedCents - $sourceTotalCents),
            'webling_writeoff_cents' => $writeoffCents,
            'source_total_cents' => $sourceTotalCents,
            'source_snapshot' => ['total_cents' => $sourceTotalCents],
        ]);
    }

    public function unpaid(int $totalCents = 5000): static
    {
        $debitorId = fake()->unique()->numberBetween(900000, 999999);

        return $this->state(fn (): array => [
            'webling_debitor_id' => $debitorId,
            'webling_invoice_number' => (string) $debitorId,
            'webling_state' => 'open',
            'webling_total_cents' => $totalCents,
            'webling_remaining_cents' => $totalCents,
            'webling_payment_verification' => DonorPaymentVerification::Unverified,
            'webling_synced_at' => now(),
            'source_total_cents' => $totalCents,
            'source_snapshot' => ['total_cents' => $totalCents],
        ]);
    }

    public function partiallyPaid(int $totalCents = 5000, int $remainingCents = 3000): static
    {
        return $this->unpaid($totalCents)->state(fn (): array => [
            'webling_state' => 'partially paid',
            'webling_remaining_cents' => $remainingCents,
        ]);
    }

    public function paidUnclear(int $totalCents = 5000): static
    {
        return $this->unpaid($totalCents)->state(fn (): array => [
            'webling_state' => 'paid',
            'webling_remaining_cents' => 0,
            'webling_payment_verification' => DonorPaymentVerification::Unclear,
        ]);
    }

    public function withoutSync(int $sourceTotalCents = 5000): static
    {
        return $this->unpaid($sourceTotalCents)->state(fn (): array => [
            'webling_state' => null,
            'webling_total_cents' => null,
            'webling_remaining_cents' => null,
            'webling_received_cents' => null,
            'webling_payment_verification' => DonorPaymentVerification::Unverified,
            'webling_synced_at' => null,
            'webling_excess_cents' => null,
            'webling_writeoff_cents' => null,
        ]);
    }

    public function remotelyDeleted(): static
    {
        return $this->unpaid()->state(fn (): array => [
            'webling_debitor_id' => null,
            'remote_deleted_at' => now(),
        ]);
    }
}
