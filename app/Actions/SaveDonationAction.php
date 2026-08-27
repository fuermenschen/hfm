<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Donation;

class SaveDonationAction
{
    /**
     * @param  array{amount_per_round:float, amount_min:?float, amount_max:?float, comment:?string, verified:bool}  $data
     */
    public function __invoke(Donation $donation, array $data): Donation
    {
        $donation->fill([
            'amount_per_round' => $data['amount_per_round'],
            'amount_min' => $data['amount_min'],
            'amount_max' => $data['amount_max'],
            'comment' => filled($data['comment']) ? trim((string) $data['comment']) : null,
            'verified' => $data['verified'],
        ])->save();

        return $donation;
    }
}
