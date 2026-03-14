<?php

namespace App\Models;

use App\Notifications\AthleteNewDonation;
use App\Notifications\DonationRegistered;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * @property Donor $donor
 * @property Athlete $athlete
 */
class Donation extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($donation) {
            // Skip notifications and logs during unit tests to speed up tests
            if (app()->runningUnitTests()) {
                return;
            }

            $donation->donor->generateLoginToken();

            $donation->donor->notify(new DonationRegistered(
                $donation->donor->first_name,
                $donation->athlete->privacy_name,
                $donation->id,
                $donation->donor->login_token
            ));

            $donation->athlete->notify(new AthleteNewDonation(
                $donation->athlete->first_name,
                $donation->donor->privacy_name,
                $donation->athlete->public_id_string,
                $donation->athlete->login_token
            ));

            // add log entry
            Log::info('Donation registered', [
                'donor' => $donation->donor->privacy_name,
                'athlete' => $donation->athlete->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_max' => $donation->amount_max,
                'amount_min' => $donation->amount_min,
                'comment' => $donation->comment,
            ]);
        });

        static::deleting(function ($donation) {
            if (! app()->runningUnitTests()) {
                // add log entry
                Log::info('Donation deleted', [
                    'donor' => $donation->donor->privacy_name,
                    'athlete' => $donation->athlete->privacy_name,
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

    protected $fillable = [
        'donor_id',
        'athlete_id',
        'amount_per_round',
        'amount_max',
        'amount_min',
        'comment',
    ];
}
