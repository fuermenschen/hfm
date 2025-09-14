<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Correct initial defaults for InvoiceSettings
        $this->migrator->add('invoiceSettings.qr_iban', 'CH0000000000000000000');
        $this->migrator->add('invoiceSettings.qr_show_amount', false);
        $this->migrator->add('invoiceSettings.creditor_name', '');
        $this->migrator->add('invoiceSettings.creditor_address1', '');
        $this->migrator->add('invoiceSettings.creditor_address2', '');
        $this->migrator->add('invoiceSettings.due_days', 14);
    }

    public function down(): void
    {
        $this->migrator->delete('invoiceSettings.qr_iban');
        $this->migrator->delete('invoiceSettings.qr_show_amount');
        $this->migrator->delete('invoiceSettings.creditor_name');
        $this->migrator->delete('invoiceSettings.creditor_address1');
        $this->migrator->delete('invoiceSettings.creditor_address2');
        $this->migrator->delete('invoiceSettings.due_days');
    }
};
