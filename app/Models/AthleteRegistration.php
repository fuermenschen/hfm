<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property DonationEvent $donationEvent
 * @property ExternalUser $externalUser
 * @property EventGroup|null $eventGroup
 * @property SportType $sportType
 * @property Partner|null $partner
 * @property Collection<int, Donation> $donations
 */
#[Fillable([
    'donation_event_id',
    'external_user_id',
    'sport_type_id',
    'partner_id',
    'event_group_id',
    'group_membership_status',
    'group_membership_role',
    'adult',
    'rounds_estimated',
    'rounds_done',
    'comment',
    'notify_previous_donors',
    'verified',
])]
class AthleteRegistration extends Model
{
    use HasFactory;

    public function donationEvent(): BelongsTo
    {
        return $this->belongsTo(DonationEvent::class);
    }

    public function externalUser(): BelongsTo
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function eventGroup(): BelongsTo
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function sportType(): BelongsTo
    {
        return $this->belongsTo(SportType::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    protected function casts(): array
    {
        return [
            'rounds_estimated' => 'integer',
            'rounds_done' => 'integer',
            'adult' => 'boolean',
            'notify_previous_donors' => 'boolean',
            'verified' => 'boolean',
            'group_membership_status' => GroupMembershipStatus::class,
            'group_membership_role' => GroupMembershipRole::class,
        ];
    }
}
