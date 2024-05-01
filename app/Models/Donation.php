<?php

namespace App\Models;

use App\Notifications\DonationRegistered;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{

    protected static function boot()
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
        });
    }

    public function donator()
    {
        return $this->belongsTo(Donator::class);
    }

    public function athlete()
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
