<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('invoiceSettings.creditor_street', '');
        $this->migrator->add('invoiceSettings.creditor_building_number', '');
        $this->migrator->add('invoiceSettings.creditor_postal_code', '');
        $this->migrator->add('invoiceSettings.creditor_city', '');

        $this->migrator->delete('invoiceSettings.creditor_address1');
        $this->migrator->delete('invoiceSettings.creditor_address2');
    }

    public function down(): void
    {
        $this->migrator->add('invoiceSettings.creditor_address1', '');
        $this->migrator->add('invoiceSettings.creditor_address2', '');

        $this->migrator->delete('invoiceSettings.creditor_street');
        $this->migrator->delete('invoiceSettings.creditor_building_number');
        $this->migrator->delete('invoiceSettings.creditor_postal_code');
        $this->migrator->delete('invoiceSettings.creditor_city');
    }
};
