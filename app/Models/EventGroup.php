<?php

declare(strict_types=1);

namespace App\Models;

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

    public function athleteRegistrations(): HasMany
    {
        return $this->hasMany(AthleteRegistration::class);
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
