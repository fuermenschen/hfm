<?php

namespace App\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAssociationDonationInvoice
{
    use AsAction;

    public function handle(
        string $first_name,
        string $last_name,
        string $address,
        int $zip_code,
        string $city,
        ?string $company_name = null,
        ?float $amount = null
    ): array {
        $filename = 'Spendenrechnung_'.$first_name.'_'.$last_name.'_VereinFuerMenschen.pdf';
        $pdf = Pdf::loadView('printables.association-donation-invoice', [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address' => $address,
            'zip_code' => $zip_code,
            'city' => $city,
            'company_name' => $company_name,
            'amount' => $amount,
        ])->setPaper('a4');

        return ['pdf' => $pdf, 'filename' => $filename];
    }
}
