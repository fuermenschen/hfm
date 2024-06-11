<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class Donator extends Model
{

    use Notifiable;

    protected $appends = [
        "privacy_name",
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($donator) {

            // add log entry
            Log::info("Donator registered", [
                "donator" => $donator->toArray(),
            ]);
        });

        static::deleting(function ($donator) {

            // delete all donations of the donator
            $donator->donations()->delete();

            // add log entry
            Log::info("Donator deleted", [
                "donator" => $donator->toArray(),
            ]);

        });
    }

    public function generateLoginToken(): void
    {
        $token = bin2hex(random_bytes(32));

        if ($this->tokenExists($token)) {
            $this->generateLoginToken();
        }

        $this->login_token = $token;
        $this->save();
    }

    private function tokenExists(string $token): bool
    {
        return Athlete::where("login_token", $token)->exists();
    }

    public function getPrivacyNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name[0]}.";
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    protected $fillable = [
        "first_name",
        "last_name",
        "address",
        "zip_code",
        "city",
        "phone_number",
        "email",
    ];
}
