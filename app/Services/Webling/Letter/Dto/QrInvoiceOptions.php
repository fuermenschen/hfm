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
        public string $creditorAddress1 = '',
        public string $creditorAddress2 = '',
        /** @var list<string> */
        public array $debtorName = [],
        /** @var list<string> */
        public array $debtorAddress1 = [],
        /** @var list<string> */
        public array $debtorAddress2 = [],
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
        // Fallback to settings if not set.
        $settings = app(InvoiceSettings::class);
        $iban = $this->iban !== '' ? $this->iban : ($settings->qr_iban ?? '');
        $withAmount = $this->withAmount || ($settings->qr_show_amount ?? false);
        $creditorName = $this->creditorName !== '' ? $this->creditorName : ($settings->creditor_name ?? '');
        $creditorAddress1 = $this->creditorAddress1 !== '' ? $this->creditorAddress1 : ($settings->creditor_address1 ?? '');
        $creditorAddress2 = $this->creditorAddress2 !== '' ? $this->creditorAddress2 : ($settings->creditor_address2 ?? '');

        return [
            'iban' => $iban,
            'customerIdentification' => $this->customerIdentification,
            'creditorName' => $creditorName,
            'creditorAddress1' => $creditorAddress1,
            'creditorAddress2' => $creditorAddress2,
            'debtorName' => $this->debtorName,
            'debtorAddress1' => $this->debtorAddress1,
            'debtorAddress2' => $this->debtorAddress2,
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
