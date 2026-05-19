<?php

use App\Components\AdminExternalUserTable;
use Livewire\Livewire;

it('renders AdminExternalUserTable', function (): void {
    Livewire::test(AdminExternalUserTable::class)
        ->assertSee('Ausgewählt: 0');
});
