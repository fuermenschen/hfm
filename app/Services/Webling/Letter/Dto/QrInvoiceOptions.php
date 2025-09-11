<?php

namespace App\Services\Webling\Letter\Dto;

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
        return [
            'iban' => $this->iban,
            'customerIdentification' => $this->customerIdentification,
            'creditorName' => $this->creditorName,
            'creditorAddress1' => $this->creditorAddress1,
            'creditorAddress2' => $this->creditorAddress2,
            'debtorName' => $this->debtorName,
            'debtorAddress1' => $this->debtorAddress1,
            'debtorAddress2' => $this->debtorAddress2,
            'additionalInformation' => $this->additionalInformation,
            'withAmount' => $this->withAmount,
            'hideLines' => $this->hideLines,
            'language' => $this->language,
            'twintQrActivated' => $this->twintQrActivated,
            'twintMethodParameters' => $this->twintMethodParameters,
            'twintBillingInformation' => $this->twintBillingInformation,
        ];
    }
}
