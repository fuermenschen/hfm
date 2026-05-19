<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\AthleteNewDonation;
use App\Notifications\DonationRegistered;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * @property Donor|null $donor
 * @property Athlete|null $athlete
 * @property ExternalUser|null $donorExternalUser
 * @property AthleteRegistration|null $athleteRegistration
 */
#[Fillable([
    'donor_id',
    'donor_external_user_id',
    'athlete_id',
    'athlete_registration_id',
    'amount_per_round',
    'amount_max',
    'amount_min',
    'comment',
])]
class Donation extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Donation $donation): void {
            // Skip notifications and logs during unit tests to speed up tests
            if (app()->runningUnitTests()) {
                return;
            }

            $donor = $donation->donor;
            $athlete = $donation->athlete;

            if ($donor === null || $athlete === null) {
                $donation->logSkippedLegacyNotification();

                // TODO: implement external-user/athlete-registration notification flow before schema cutover writes (#126).

                return;
            }

            $donor->generateLoginToken();

            $donor->notify(new DonationRegistered(
                $donor->first_name,
                $athlete->privacy_name
            ));

            $athlete->notify(new AthleteNewDonation(
                $athlete->first_name,
                $donor->privacy_name,
                $athlete->public_id_string
            ));

            // add log entry
            Log::info('Donation registered', [
                'donor' => $donor->privacy_name,
                'athlete' => $athlete->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_max' => $donation->amount_max,
                'amount_min' => $donation->amount_min,
                'comment' => $donation->comment,
            ]);
        });

        static::deleting(function (Donation $donation): void {
            if (! app()->runningUnitTests()) {
                $donorPrivacyName = $donation->donor?->privacy_name;
                $athletePrivacyName = $donation->athlete?->privacy_name;

                // add log entry
                Log::info('Donation deleted', [
                    'donor' => $donorPrivacyName,
                    'athlete' => $athletePrivacyName,
                    'amount_per_round' => $donation->amount_per_round,
                    'amount_max' => $donation->amount_max,
                    'amount_min' => $donation->amount_min,
                    'comment' => $donation->comment,
                ]);
            }
        });
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function donorExternalUser(): BelongsTo
    {
        return $this->belongsTo(ExternalUser::class, 'donor_external_user_id');
    }

    public function athleteRegistration(): BelongsTo
    {
        return $this->belongsTo(AthleteRegistration::class);
    }

    protected function logSkippedLegacyNotification(): void
    {
        Log::warning('Donation registered without legacy relations; skipping legacy notifications', [
            'donation_id' => $this->id,
            'donor_id' => $this->donor_id,
            'athlete_id' => $this->athlete_id,
            'donor_external_user_id' => $this->donor_external_user_id,
            'athlete_registration_id' => $this->athlete_registration_id,
            'athlete_registration_event_id' => $this->athleteRegistration?->donation_event_id,
        ]);
    }
}
