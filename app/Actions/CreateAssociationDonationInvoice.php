<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class CreateAssociationDonationInvoice
{
    use AsAction;

    public function handle(
        string $first_name,
        string $last_name,
        string $address,
        string $zip_code,
        string $city,
        ?string $company_name = null,
        ?float $amount = null
    ) {
        // TODO: implement actual invoice generation
        dump('Action is yet to be implemented');
    }
}
