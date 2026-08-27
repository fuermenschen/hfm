<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Faq;

class DeleteFaqAction
{
    public function handle(Faq $faq): void
    {
        throw_if(
            $faq->donationEvents()->exists(),
            \RuntimeException::class,
            'FAQ ist noch mindestens einem Anlass zugeordnet und kann nicht gelöscht werden.',
        );

        $faq->delete();
    }
}
