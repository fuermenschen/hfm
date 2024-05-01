<?php

namespace App\Models;

use App\Notifications\NewLoginToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Donator extends Model
{

    use Notifiable;

    protected $appends = [
        "privacy_name",
    ];

    public function newTokenAndNotify(): void
    {
        $this->generateLoginToken();
        $this->notify(new NewLoginToken($this->first_name, $this->login_token, "verify-donation"));
    }

    public function generateLoginToken(): void
    {
        $token = bin2hex(random_bytes(32));

        if ($this->tokenExists($token)) {
            $this->generateLoginToken();
        }

        $this->login_token = $token;
        $this->login_token_expires_at = now()->addDays(config("app.login_token_expiry_days"));
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

    protected function casts(): array
    {
        return [
            "login_token_expires_at" => "datetime",
        ];
    }
}
