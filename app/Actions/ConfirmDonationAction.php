<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Donation;
use App\Models\ExternalUser;
use Illuminate\Auth\Access\AuthorizationException;

class ConfirmDonationAction
{
    public function __invoke(Donation $donation, ExternalUser $externalUser): void
    {
        throw_if($donation->donor_external_user_id !== $externalUser->id, AuthorizationException::class, 'Diese Spende gehört nicht zu deinem Profil.');

        Donation::query()
            ->whereKey($donation->id)
            ->where('verified', false)
            ->update(['verified' => true]);
    }
}
