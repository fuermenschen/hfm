<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use Database\Factories\EventGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $donation_event_id
 * @property string $name
 * @property string $normalized_name
 * @property DonationEvent $donationEvent
 * @property Collection<int, AthleteRegistration> $athleteRegistrations
 */
#[Fillable([
    'donation_event_id',
    'name',
])]
class EventGroup extends Model
{
    /** @use HasFactory<EventGroupFactory> */
    use HasFactory;

    public function donationEvent(): BelongsTo
    {
        return $this->belongsTo(DonationEvent::class);
    }

    /** @return HasMany<AthleteRegistration, $this> */
    public function athleteRegistrations(): HasMany
    {
        return $this->hasMany(AthleteRegistration::class);
    }

    /** @return HasMany<AthleteRegistration, $this> */
    public function acceptedAdmins(): HasMany
    {
        return $this->athleteRegistrations()
            ->where('verified', true)
            ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
            ->where('group_membership_role', GroupMembershipRole::Admin->value);
    }

    public static function normalizeName(string $name): string
    {
        return Str::of($name)->trim()->ascii()->lower()->toString();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: function (string $value): array {
                $name = trim($value);

                return [
                    'name' => $name,
                    'normalized_name' => self::normalizeName($name),
                ];
            },
        );
    }
}
