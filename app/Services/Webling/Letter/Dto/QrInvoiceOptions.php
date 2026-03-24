<?php

namespace App\Services\Webling\Letter\Dto;

use App\Settings\InvoiceSettings;

/**
 * QR invoice options (subset of fields we need initially).
 */
class QrInvoiceOptions
{
    public function __construct(
        public string $iban = '',
        public string $customerIdentification = '',
        public string $creditorName = '',
        public string $creditorStreet = '',
        public string $creditorBuildingNumber = '',
        public string $creditorPostalCode = '',
        public string $creditorCity = '',
        /** @var list<string> */
        public array $debtorName = [],
        /** @var list<string> */
        public array $debtorStreet = [],
        /** @var list<string> */
        public array $debtorBuildingNumber = [],
        /** @var list<string> */
        public array $debtorPostalCode = [],
        /** @var list<string> */
        public array $debtorCity = [],
        public string $additionalInformation = '',
        public bool $withAmount = false,
        public bool $hideLines = false,
        public string $language = 'de',
        public bool $twintQrActivated = false,
        public string $twintMethodParameters = '',
        public string $twintBillingInformation = '',
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $settings = app(InvoiceSettings::class);
        $iban = $this->iban !== '' ? $this->iban : ($settings->qr_iban ?? '');
        $withAmount = $this->withAmount || ($settings->qr_show_amount ?? false);
        $creditorName = $this->creditorName !== '' ? $this->creditorName : ($settings->creditor_name ?? '');
        $creditorStreet = $this->creditorStreet !== '' ? $this->creditorStreet : ($settings->creditor_street ?? '');
        $creditorBuildingNumber = $this->creditorBuildingNumber !== '' ? $this->creditorBuildingNumber : ($settings->creditor_building_number ?? '');
        $creditorPostalCode = $this->creditorPostalCode !== '' ? $this->creditorPostalCode : ($settings->creditor_postal_code ?? '');
        $creditorCity = $this->creditorCity !== '' ? $this->creditorCity : ($settings->creditor_city ?? '');

        return [
            'iban' => $iban,
            'customerIdentification' => $this->customerIdentification,
            'creditorName' => $creditorName,
            'creditorStreet' => $creditorStreet,
            'creditorBuildingNumber' => $creditorBuildingNumber,
            'creditorPostalCode' => $creditorPostalCode,
            'creditorCity' => $creditorCity,
            'debtorName' => $this->debtorName,
            'debtorStreet' => $this->debtorStreet,
            'debtorBuildingNumber' => $this->debtorBuildingNumber,
            'debtorPostalCode' => $this->debtorPostalCode,
            'debtorCity' => $this->debtorCity,
            'additionalInformation' => $this->additionalInformation,
            'withAmount' => $withAmount,
            'hideLines' => $this->hideLines,
            'language' => $this->language,
            'twintQrActivated' => $this->twintQrActivated,
            'twintMethodParameters' => $this->twintMethodParameters,
            'twintBillingInformation' => $this->twintBillingInformation,
        ];
    }
}
