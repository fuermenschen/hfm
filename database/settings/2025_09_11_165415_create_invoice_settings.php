<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Correct initial defaults for InvoiceSettings
        $this->migrator->add('invoiceSettings.qr_iban', 'CH0000000000000000000');
        $this->migrator->add('invoiceSettings.qr_show_amount', false);
        $this->migrator->add('invoiceSettings.due_days', 14);
    }
};
