<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\AthleteRegistered;
use App\Notifications\GenericMessage;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property Partner|null $partner
 * @property DonationEvent|null $donationEvent
 * @property string $privacy_name
 * @property string $full_name
 * @property string $public_id_string
 */
#[Appends([
    'full_name',
    'privacy_name',
    'public_id_string',
])]
#[Fillable([
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
    'donation_event_id',
    'comment',
])]
#[Hidden([
    'login_token',
    'login_token_expires_at',
    'email_verified_at',
    'created_at',
    'updated_at',
])]
class Athlete extends Model
{
    use HasFactory;
    use Notifiable;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($athlete) {
            $athlete->public_id = $athlete->generatePublicId();
        });

        static::created(function ($athlete) {
            // Skip notifications and logs during unit tests to speed up the test suite
            if (app()->runningUnitTests()) {
                return;
            }

            try {
                $athlete->notify(new AthleteRegistered(
                    $athlete->first_name,
                    $athlete->public_id_string
                ));
            } catch (\Throwable $throwable) {
                Log::error('Failed to send AthleteRegistered notification', [
                    'athlete_id' => $athlete->id,
                    'email' => $athlete->email,
                    'error' => $throwable->getMessage(),
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

    // Athlete model scheduled for removal; suppress dead-code noise until cleanup.
    // TODO(dead-code): Remove ignores when Athlete domain removal lands.
    // @phpstan-ignore-next-line shipmonk.deadMethod
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
        return Athlete::query()->where('public_id', $token)->exists();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(get: function (): string {
            return sprintf('%s %s', $this->first_name, $this->last_name);
        });
    }

    protected function privacyName(): Attribute
    {
        return Attribute::make(get: function (): string {
            return sprintf('%s %s.', $this->first_name, $this->last_name[0]);
        });
    }

    protected function publicIdString(): Attribute
    {
        return Attribute::make(get: function (): string {
            // convert the public_id to a string with six digits
            $publicId = str_pad((string) $this->public_id, 6, '0', STR_PAD_LEFT);

            // return the formatted string
            return substr($publicId, 0, 3).'-'.substr($publicId, 3);
        });
    }

    public function sportType(): BelongsTo
    {
        return $this->belongsTo(SportType::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function donationEvent(): BelongsTo
    {
        return $this->belongsTo(DonationEvent::class);
    }

    protected function casts(): array
    {
        return [
            'public_id' => 'integer',
            'rounds_estimated' => 'integer',
            'rounds_done' => 'integer',
            'donation_event_id' => 'integer',
        ];
    }
}
