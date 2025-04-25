<?php

namespace App\Models;

use App\Notifications\AthleteNewDonation;
use App\Notifications\DonationRegistered;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Donation extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($donation) {
            $donation->donator->generateLoginToken();

            $donation->donator->notify(new DonationRegistered(
                $donation->donator->first_name,
                $donation->athlete->privacy_name,
                $donation->id,
                $donation->donator->login_token
            ));

            $donation->athlete->notify(new AthleteNewDonation(
                $donation->athlete->first_name,
                $donation->donator->privacy_name,
                $donation->athlete->public_id_string,
                $donation->athlete->login_token
            ));

            // add log entry
            Log::info('Donation registered', [
                'donator' => $donation->donator->privacy_name,
                'athlete' => $donation->athlete->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_max' => $donation->amount_max,
                'amount_min' => $donation->amount_min,
                'comment' => $donation->comment,
            ]);
        });

        static::deleting(function ($donation) {
            // add log entry
            Log::info('Donation deleted', [
                'donator' => $donation->donator->privacy_name,
                'athlete' => $donation->athlete->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_max' => $donation->amount_max,
                'amount_min' => $donation->amount_min,
                'comment' => $donation->comment,
            ]);
        });
    }

    public function donator(): BelongsTo
    {
        return $this->belongsTo(Donator::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    protected $fillable = [
        'donator_id',
        'athlete_id',
        'amount_per_round',
        'amount_max',
        'amount_min',
        'comment',
    ];
}
