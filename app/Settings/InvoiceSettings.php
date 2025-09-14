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
     * Default creditor address line 1 for QR invoices.
     */
    public string $creditor_address1 = '';

    /**
     * Default creditor address line 2 for QR invoices.
     */
    public string $creditor_address2 = '';

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
            'creditor_address1' => 'nullable|string',
            'creditor_address2' => 'nullable|string',
            'due_days' => 'required|integer|min:1',
        ];
    }

    public static function titles(): array
    {
        return [
            'qr_iban' => 'QR IBAN',
            'qr_show_amount' => 'Betrag im QR anzeigen',
            'creditor_name' => 'Name der Empfängerin',
            'creditor_address1' => 'Adresse der Empfängerin',
            'creditor_address2' => 'PLZ Ort der Empfängerin',
            'due_days' => 'Anzahl Tage Zahlungsfrist',
        ];
    }

    public static function descriptions(): array
    {
        return [
            'qr_iban' => 'Die IBAN, welche für Schweizer QR-Rechnungen verwendet werden soll.',
            'qr_show_amount' => 'Wenn aktiviert, wird der Rechnungsbetrag im QR-Code vorausgefüllt.',
            'creditor_name' => 'Name des Empfängers auf einer QR-Rechnung.',
            'creditor_address1' => 'Adresse des Empfängers für QR-Rechnungen.',
            'creditor_address2' => 'PLZ und Ort des Empfängers für QR-Rechnungen.',
            'due_days' => 'Die Rechnungen sind fällig heute + X Tage.',
        ];
    }
}
