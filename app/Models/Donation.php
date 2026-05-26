<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Athlete|null $athlete
 * @property ExternalUser|null $donorExternalUser
 * @property AthleteRegistration|null $athleteRegistration
 */
#[Fillable([
    'donor_external_user_id',
    'athlete_id',
    'athlete_registration_id',
    'amount_per_round',
    'amount_max',
    'amount_min',
    'comment',
])]
class Donation extends Model
{
    use HasFactory;

    // TODO(refactor-external-user): Dispatch donation-created domain event + notifications from external-user graph.

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function donorExternalUser(): BelongsTo
    {
        return $this->belongsTo(ExternalUser::class, 'donor_external_user_id');
    }

    public function athleteRegistration(): BelongsTo
    {
        return $this->belongsTo(AthleteRegistration::class);
    }

    public function donorIdentity(): ?ExternalUser
    {
        return $this->donorExternalUser;
    }

    public function donorPrivacyName(): string
    {
        $donorIdentity = $this->donorIdentity();

        if ($donorIdentity instanceof ExternalUser) {
            return $donorIdentity->privacy_name;
        }

        return 'Legacy Spender:in';
    }
}
