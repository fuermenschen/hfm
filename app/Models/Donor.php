<?php

namespace App\Models;

use App\Notifications\GenericMessage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @property Collection|Donation[] $donations
 * @property string $privacy_name
 * @property string $full_name
 * @property string $public_id_string
 */
class Donor extends Model
{
    use HasFactory;
    use Notifiable;

    protected $table = 'donors';

    protected $appends = [
        'privacy_name',
    ];

    protected $casts = [
        'webling_data' => 'array',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'address',
        'zip_code',
        'city',
        'country_of_residence',
        'phone_number',
        'email',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($donor) {
            $donor->generateLoginToken(false);
        });

        static::created(function ($donor) {
            if (! app()->runningUnitTests()) {
                Log::info('Donor registered', [
                    'donor' => $donor->toArray(),
                ]);
            }
        });

        static::deleting(function ($donor) {
            $donor->donations()->delete();

            if (! app()->runningUnitTests()) {
                try {
                    $email = $donor->email;
                    $message = 'Du wurdest als Spender:in gelöscht.';
                    $subject = 'Deine Registrierung wurde gelöscht';
                    $firstName = $donor->first_name;
                    Notification::route('mail', $email)->notify(new GenericMessage(
                        $message,
                        $subject,
                        $firstName)
                    );
                } catch (\Throwable $exception) {
                    Log::error('Failed to send donor deletion notification', [
                        'donor_id' => $donor->id,
                        'email' => $donor->email,
                        'error' => $exception->getMessage(),
                    ]);
                }

                Log::info('Donor deleted', [
                    'donor' => $donor->toArray(),
                ]);
            }
        });
    }

    public function generateLoginToken(bool $persist = true): void
    {
        if (! empty($this->login_token)) {
            return;
        }

        do {
            $token = bin2hex(random_bytes(32));
        } while ($this->tokenExists($token));

        $this->login_token = $token;

        if ($persist && $this->exists) {
            $this->save();
        }
    }

    public function tokenExists(string $token): bool
    {
        return self::where('login_token', $token)->exists() || Athlete::where('login_token', $token)->exists();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    public function getPrivacyNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name[0]}.";
    }
}
