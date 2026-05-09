<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\GenericMessage;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @property Collection|Donation[] $donations
 * @property string $privacy_name
 * @property string $full_name
 * @property string $public_id_string
 * @property array|null $webling_data
 * @property Carbon|null $invoice_sent_at
 * @property Carbon|null $invoice_reminder_sent_at
 */
#[Appends([
    'privacy_name',
])]
#[Fillable([
    'first_name',
    'last_name',
    'address',
    'zip_code',
    'city',
    'country_of_residence',
    'phone_number',
    'email',
])]
#[Table(name: 'donors')]
class Donor extends Model
{
    use HasFactory;
    use Notifiable;

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
        if (self::query()->where('login_token', $token)->exists()) {
            return true;
        }

        return (bool) Athlete::query()->where('login_token', $token)->exists();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    protected function privacyName(): Attribute
    {
        return Attribute::make(get: function (): string {
            return sprintf('%s %s.', $this->first_name, $this->last_name[0]);
        });
    }

    protected function casts(): array
    {
        return [
            'webling_data' => 'array',
            'invoice_sent_at' => 'datetime',
            'invoice_reminder_sent_at' => 'datetime',
        ];
    }
}
