<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;

class SaveDonationEventAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(?DonationEvent $donationEvent, array $data): DonationEvent
    {
        $donationEvent ??= new DonationEvent;

        $content = array_replace_recursive(
            $donationEvent->content ?? [],
            is_array($data['content'] ?? null) ? $data['content'] : [],
        );

        unset($data['content']);
        unset($data['timezone']);

        $donationEvent->fill([
            'timezone' => 'Europe/Zurich',
            ...$data,
            'content' => $content,
        ])->save();

        return $donationEvent;
    }
}
