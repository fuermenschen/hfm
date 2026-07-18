<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sponsor;

class DeleteSponsorAction
{
    public function handle(Sponsor $sponsor): void
    {
        throw_if(
            $sponsor->donationEvents()->exists(),
            \RuntimeException::class,
            'Sponsor:in ist noch einem Anlass zugeordnet und kann nicht gelöscht werden.',
        );

        $sponsor->delete();
    }
}
