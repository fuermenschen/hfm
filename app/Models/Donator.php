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
class Donator extends Model
{
    use HasFactory;
    use Notifiable;

    protected $appends = [
        'privacy_name',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($donator) {

            // create login token
            $donator->generateLoginToken();

            // add log entry
            Log::info('Donator registered', [
                'donator' => $donator->toArray(),
            ]);
        });

        static::deleting(function ($donator) {

            // delete all donations of the donator
            $donator->donations()->delete();

            // notify the donator that their account has been deleted
            // directly use the email address because the donator is being deleted
            try {
                $email = $donator->email;
                $message = 'Du wurdest als Spender:in gelöscht.';
                $subject = 'Deine Registrierung wurde gelöscht';
                $first_name = $donator->first_name;
                Notification::route('mail', $email)->notify(new GenericMessage(
                    $message,
                    $subject,
                    $first_name)
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send donator deletion notification', [
                    'donator_id' => $donator->id,
                    'email' => $donator->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // add log entry
            Log::info('Donator deleted', [
                'donator' => $donator->toArray(),
            ]);

        });
    }

    public function generateLoginToken(): void
    {
        if (! empty($this->login_token)) {
            return;
        }

        do {
            $token = bin2hex(random_bytes(32));
        } while ($this->tokenExists($token));

        $this->login_token = $token;
        $this->save();
    }

    public function tokenExists(string $token): bool
    {
        return self::where('login_token', $token)->exists();
    }

    public function getPrivacyNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name[0]}.";
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

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
}
