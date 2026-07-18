<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\Partner;

class DeletePartnerAction
{
    public function handle(Partner $partner): void
    {
        throw_if(
            $partner->donationEvents()->exists(),
            \RuntimeException::class,
            'Partner:in ist noch einem Anlass zugeordnet und kann nicht gelöscht werden.',
        );

        throw_if(
            AthleteRegistration::query()->whereBelongsTo($partner)->exists(),
            \RuntimeException::class,
            'Partner:in wird noch von mindestens einer Athlet:innen-Registrierung verwendet und kann nicht gelöscht werden.',
        );

        $partner->delete();
    }
}
