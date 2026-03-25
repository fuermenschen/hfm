<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('eventSettings.current_event_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('eventSettings.current_event_id');
    }
};
