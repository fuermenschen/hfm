<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('invoiceSettings.creditor_care_of', '');
    }

    public function down(): void
    {
        $this->migrator->delete('invoiceSettings.creditor_care_of');
    }
};
