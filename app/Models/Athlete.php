<?php

namespace App\Models;

use App\Notifications\AthleteRegistered;
use App\Notifications\GenericMessage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @property Donation[] $donations
 * @property SportType $sportType
 * @property Partner $partner
 * @property string $privacy_name
 * @property string $full_name
 * @property string $public_id_string
 */
class Athlete extends Model
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'address',
        'zip_code',
        'city',
        'phone_number',
        'email',
        'adult',
        'sport_type_id',
        'rounds_estimated',
        'partner_id',
        'comment',
    ];

    protected $casts = [
        'public_id' => 'integer',
        'rounds_estimated' => 'integer',
        'rounds_done' => 'integer',
    ];

    protected $hidden = [
        'login_token',
        'login_token_expires_at',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'full_name',
        'privacy_name',
        'public_id_string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($athlete) {
            $athlete->public_id = $athlete->generatePublicId();

            // Generate login token before first save to avoid an extra update query
            $athlete->generateLoginToken(false);
        });

        static::created(function ($athlete) {
            // Skip notifications and logs during unit tests to speed up the test suite
            if (app()->runningUnitTests()) {
                return;
            }

            try {
                $athlete->notify(new AthleteRegistered(
                    $athlete->first_name,
                    $athlete->public_id_string,
                    $athlete->login_token
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send AthleteRegistered notification', [
                    'athlete_id' => $athlete->id,
                    'email' => $athlete->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // add log entry
            Log::info('Athlete created', [
                'athlete' => $athlete->toArray(),
            ]);

        });

        static::deleting(function ($athlete) {

            // delete all donations of the athlete
            $athlete->donations()->delete();

            if (! app()->runningUnitTests()) {
                // notify the athlete that their account has been deleted
                // directly use the email address because the athlete is being deleted
                try {
                    $email = $athlete->email;
                    $message = 'Du wurdest als Sportler:in gelöscht.';
                    $subject = 'Deine Registrierung wurde gelöscht';
                    $first_name = $athlete->first_name;
                    Notification::route('mail', $email)->notify(new GenericMessage(
                        $message,
                        $subject,
                        $first_name)
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to send athlete deletion notification', [
                        'athlete_id' => $athlete->id,
                        'email' => $athlete->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // add log entry
            Log::info('Athlete deleted', [
                'athlete' => $athlete->toArray(),
            ]);

        });
    }

    public function generatePublicId(): int
    {
        $token = random_int(100000, 999999);

        if ($this->idExists($token)) {
            return $this->generatePublicId();
        }

        return $token;
    }

    public function idExists(int $token): bool
    {
        return Athlete::where('public_id', $token)->exists();
    }

    public function generateLoginToken(bool $persist = true): void
    {
        // return if token is non empty
        if (! empty($this->login_token)) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        if ($this->tokenExists($token)) {
            $this->generateLoginToken($persist);

            return;
        }

        $this->login_token = $token;

        if ($persist && $this->exists) {
            $this->save();
        }
    }

    public function tokenExists(string $token): bool
    {
        return Athlete::where('login_token', $token)->exists() || Donor::where('login_token', $token)->exists();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function getFullNameAttribute(): string
    {
        return "$this->first_name $this->last_name";
    }

    // make a string in the format ###-###

    public function getPrivacyNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name[0]}.";
    }

    public function getPublicIdStringAttribute(): string
    {
        // convert the public_id to a string with six digits
        $publicId = str_pad((string) $this->public_id, 6, '0', STR_PAD_LEFT);

        // return the formatted string
        return substr($publicId, 0, 3).'-'.substr($publicId, 3);
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
