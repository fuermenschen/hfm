<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('weblingApi.bank_account_id');
        $this->migrator->add('weblingApi.excess_income_account_id');
    }

    public function down(): void
    {
        $this->migrator->delete('weblingApi.bank_account_id');
        $this->migrator->delete('weblingApi.excess_income_account_id');
    }
};
