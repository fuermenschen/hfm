<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('weblingApi.api_url', 'https://api.test/v1');
        $this->migrator->addEncrypted('weblingApi.api_key', 'goodluckwiththatkey');
        $this->migrator->add('weblingApi.accounting_period_id', 1);
        $this->migrator->add('weblingApi.debit_account_id', 2);
        $this->migrator->add('weblingApi.credit_account_id', 1);
    }

    public function down(): void
    {
        $this->migrator->delete('weblingApi.api_url');
        $this->migrator->delete('weblingApi.api_key');
        $this->migrator->delete('weblingApi.accounting_period_id');
        $this->migrator->delete('weblingApi.debit_account_id');
        $this->migrator->delete('weblingApi.credit_account_id');
    }
};
