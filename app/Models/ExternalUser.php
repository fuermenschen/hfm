<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property string $full_name
 * @property string $privacy_name
 * @property string $public_id_string
 * @property Collection<int, AthleteRegistration> $athleteRegistrations
 * @property Collection<int, Donation> $donationsAsDonor
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
    'country_of_residence',
    'phone_number',
    'email',
])]
#[Hidden([
    'remember_token',
])]
class ExternalUser extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $externalUser): void {
            if (empty($externalUser->uuid)) {
                $externalUser->uuid = (string) Str::uuid();
            }

            if (empty($externalUser->public_id)) {
                $externalUser->public_id = self::generatePublicId();
            }
        });
    }

    public function athleteRegistrations(): HasMany
    {
        return $this->hasMany(AthleteRegistration::class);
    }

    public function donationsAsDonor(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_external_user_id');
    }

    public function privacyName(): string
    {
        return $this->privacy_name;
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(get: function (): string {
            return sprintf('%s %s', $this->first_name, $this->last_name);
        });
    }

    protected function getPrivacyNameAttribute(): string
    {
        return sprintf('%s %s.', $this->first_name, Str::substr($this->last_name, 0, 1));
    }

    protected function publicIdString(): Attribute
    {
        return Attribute::make(get: function (): string {
            return substr($this->public_id, 0, 3).'-'.substr($this->public_id, 3);
        });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'legacy_athlete_id' => 'integer',
            'legacy_donor_id' => 'integer',
        ];
    }

    protected static function generatePublicId(): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        // Intentional: keep this simple. Extremely unlikely collisions are enforced by DB unique constraint.

        do {
            $id = '';

            for ($i = 0; $i < 6; $i++) {
                $id .= $charset[random_int(0, strlen($charset) - 1)];
            }
        } while (self::query()->where('public_id', $id)->exists());

        return $id;
    }
}
