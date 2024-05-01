<?php

namespace App\Models;

use App\Notifications\AthleteNewDonation;
use App\Notifications\DonationRegistered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{

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
                $donation->athlete->login_token
            ));
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
        "donator_id",
        "athlete_id",
        "amount_per_round",
        "amount_max",
        "amount_min",
        "comment",
    ];
}
