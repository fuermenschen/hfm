<?php

namespace App\Models;

use App\Notifications\AthleteRegistered;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Athlete extends Model
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        "first_name",
        "last_name",
        "address",
        "zip_code",
        "city",
        "phone_number",
        "email",
        "adult",
        "sport_type_id",
        "rounds_estimated",
        "partner_id",
        "comment",
    ];

    protected $casts = [
        "public_id" => "integer",
        "rounds_estimated" => "integer",
        "rounds_done" => "integer",
    ];

    protected $hidden = [
        "login_token",
        "login_token_expires_at",
        "email_verified_at",
        "created_at",
        "updated_at",
    ];

    protected $appends = [
        "full_name",
        "privacy_name",
        "public_id_string",
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($athlete) {
            $athlete->public_id = $athlete->generatePublicId();
        });

        static::created(function ($athlete) {
            $athlete->login_token = bin2hex(random_bytes(32));
            $athlete->login_token_expires_at = now()->addDays(config("login_token_expiry_days"));
            $athlete->save();

            $athlete->notify(new AthleteRegistered($athlete));
        });
    }

    private function generatePublicId(): int
    {
        $token = mt_rand(100000, 999999);

        if ($this->idExists($token)) {
            return $this->generatePublicId();
        }

        return $token;
    }

    private function idExists(int $token): bool
    {
        return Athlete::where("public_id", $token)->exists();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPrivacyNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name[0]}.";
    }

    // make a string in the format ###-###
    public function getPublicIdStringAttribute(): string
    {
        // convert the public_id to a string with six digits
        $publicId = str_pad($this->public_id, 6, "0", STR_PAD_LEFT);
        // return the formatted string
        return substr($publicId, 0, 3) . "-" . substr($publicId, 3);
    }

    public function sportType(): BelongsTo
    {
        return $this->belongsTo(SportType::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
