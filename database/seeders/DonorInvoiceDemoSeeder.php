<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DonorPaymentVerification;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonorInvoiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        throw_unless(app()->environment(['local', 'testing']), \RuntimeException::class, 'Invoice demo data is local-only.');

        DB::transaction(function (): void {
            $event = DonationEvent::query()->firstOrCreate(['slug' => 'rechnungs-demo'], DonationEvent::factory()->year(2026)->make([
                'slug' => 'rechnungs-demo', 'title' => 'Rechnungs-Demo', 'is_published' => false,
            ])->getAttributes());
            $athlete = ExternalUser::query()->firstOrCreate(['email' => 'invoice-demo-athlete@example.test'], ExternalUser::factory()->make([
                'email' => 'invoice-demo-athlete@example.test', 'first_name' => 'Demo', 'last_name' => 'Sportlerin',
            ])->getAttributes());
            $registration = AthleteRegistration::query()->firstOrCreate([
                'donation_event_id' => $event->id, 'external_user_id' => $athlete->id,
            ], AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->make(['rounds_done' => 10])->getAttributes());

            $scenarios = [
                'Bezahlt' => ['webling_state' => 'paid', 'webling_remaining_cents' => 0, 'webling_received_cents' => 5000, 'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_payment_date' => '2026-10-12'],
                'Mehrzahlung' => ['source_total_cents' => 2600, 'webling_total_cents' => 3500, 'webling_state' => 'paid', 'webling_remaining_cents' => 0, 'webling_received_cents' => 3500, 'webling_excess_cents' => 900, 'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_payment_date' => '2026-10-12'],
                'Unbezahlt' => ['webling_state' => 'open'],
                'Teilbezahlt' => ['webling_state' => 'partially paid', 'webling_remaining_cents' => 3000],
                'Unklar' => ['webling_state' => 'paid', 'webling_remaining_cents' => 0, 'webling_payment_verification' => DonorPaymentVerification::Unclear],
                'Abschreibung' => ['webling_state' => 'paid', 'webling_remaining_cents' => 0, 'webling_received_cents' => 4600, 'webling_writeoff_cents' => 400, 'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_payment_date' => '2026-10-12'],
                'Nicht abgeglichen' => ['webling_state' => null, 'webling_remaining_cents' => null, 'webling_total_cents' => null, 'webling_synced_at' => null, 'webling_writeoff_cents' => null, 'webling_excess_cents' => null],
                'Ohne Rechnung' => null,
                'Gelöscht' => ['webling_debitor_id' => null, 'remote_deleted_at' => now()],
            ];
            foreach ($scenarios as $name => $attributes) {
                $email = 'invoice-demo-'.array_search($name, array_keys($scenarios), true).'@example.test';
                $donor = ExternalUser::query()->firstOrCreate(['email' => $email], ExternalUser::factory()->make([
                    'email' => $email,
                    'first_name' => 'Demo', 'last_name' => $name, 'city' => 'Winterthur', 'country_of_residence' => 'CH', 'phone_number' => '+41791234567',
                ])->getAttributes());
                Donation::query()->updateOrCreate([
                    'donor_external_user_id' => $donor->id, 'athlete_registration_id' => $registration->id,
                ], ['amount_per_round' => 5, 'amount_min' => null, 'amount_max' => null, 'verified' => true]);
                if ($attributes === null) {
                    continue;
                }

                $original = $attributes['source_total_cents'] ?? 5000;
                DonorEventInvoice::query()->updateOrCreate([
                    'external_user_id' => $donor->id, 'donation_event_id' => $event->id,
                ], $attributes + [
                    'webling_debitor_id' => 900000 + $donor->id, 'webling_invoice_number' => 'DEMO-'.$donor->id,
                    'source_total_cents' => $original, 'source_snapshot' => ['total_cents' => $original],
                    'webling_total_cents' => 5000, 'webling_remaining_cents' => 5000,
                    'webling_received_cents' => null, 'webling_payment_date' => null,
                    'webling_payment_verification' => DonorPaymentVerification::Unverified,
                    'webling_excess_cents' => 0, 'webling_writeoff_cents' => 0,
                    'webling_synced_at' => now(), 'invoice_sent_at' => now()->subDays(10),
                    'webling_due_date' => now()->subDays(3)->toDateString(), 'remote_deleted_at' => null,
                ]);
            }
        });
    }
}
