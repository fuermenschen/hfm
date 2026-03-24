<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InvoiceSettings extends Settings
{
    /**
     * The IBAN to be used for Swiss QR invoices.
     */
    public string $qr_iban = '';

    /**
     * Whether to include the amount in the generated QR code by default.
     */
    public bool $qr_show_amount = false;

    /**
     * Default creditor name for QR invoices.
     */
    public string $creditor_name = '';

    /**
     * Default creditor street for QR invoices.
     */
    public string $creditor_street = '';

    /**
     * Default creditor building number for QR invoices.
     */
    public string $creditor_building_number = '';

    /**
     * Default creditor postal code for QR invoices.
     */
    public string $creditor_postal_code = '';

    /**
     * Default creditor city for QR invoices.
     */
    public string $creditor_city = '';

    /**
     * Number of days until an invoice is due.
     */
    public int $due_days = 14;

    public static function group(): string
    {
        return 'invoiceSettings';
    }

    public static function settingsDetails(): array
    {
        $title = 'Rechnungen';
        $description = 'Einstellungen für Rechnungen und QR-Zahlungen.';

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    public static function rules(): array
    {
        return [
            'qr_iban' => 'required|regex:/^CH\d{19}$/',
            'qr_show_amount' => 'required|boolean',
            'creditor_name' => 'nullable|string',
            'creditor_street' => 'nullable|string',
            'creditor_building_number' => 'nullable|string',
            'creditor_postal_code' => 'nullable|string',
            'creditor_city' => 'nullable|string',
            'due_days' => 'required|integer|min:1',
        ];
    }

    public static function titles(): array
    {
        return [
            'qr_iban' => 'QR IBAN',
            'qr_show_amount' => 'Betrag im QR anzeigen',
            'creditor_name' => 'Name der Empfängerin',
            'creditor_street' => 'Strasse der Empfängerin',
            'creditor_building_number' => 'Hausnummer der Empfängerin',
            'creditor_postal_code' => 'PLZ der Empfängerin',
            'creditor_city' => 'Ort der Empfängerin',
            'due_days' => 'Anzahl Tage Zahlungsfrist',
        ];
    }

    public static function descriptions(): array
    {
        return [
            'qr_iban' => 'Die IBAN, welche für Schweizer QR-Rechnungen verwendet werden soll.',
            'qr_show_amount' => 'Wenn aktiviert, wird der Rechnungsbetrag im QR-Code vorausgefüllt.',
            'creditor_name' => 'Name des Empfängers auf einer QR-Rechnung.',
            'creditor_street' => 'Strasse des Empfängers für QR-Rechnungen.',
            'creditor_building_number' => 'Hausnummer des Empfängers für QR-Rechnungen.',
            'creditor_postal_code' => 'PLZ des Empfängers für QR-Rechnungen.',
            'creditor_city' => 'Ort des Empfängers für QR-Rechnungen.',
            'due_days' => 'Die Rechnungen sind fällig heute + X Tage.',
        ];
    }
}
