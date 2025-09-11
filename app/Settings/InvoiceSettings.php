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
            'due_days' => 'required|integer|min:1',
        ];
    }

    public static function titles(): array
    {
        return [
            'qr_iban' => 'QR IBAN',
            'qr_show_amount' => 'Betrag im QR anzeigen',
            'due_days' => 'Anzahl Tage Zahlungsfrist',
        ];
    }

    public static function descriptions(): array
    {
        return [
            'qr_iban' => 'Die IBAN, welche für Schweizer QR-Rechnungen verwendet werden soll.',
            'qr_show_amount' => 'Wenn aktiviert, wird der Rechnungsbetrag im QR-Code vorausgefüllt.',
            'due_days' => 'Die Rechnungen sind fällig heute + X Tage.',
        ];
    }
}
