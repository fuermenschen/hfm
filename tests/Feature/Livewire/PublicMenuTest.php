<?php

use App\Components\PublicMenu;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(PublicMenu::class)
        ->assertStatus(200);
});
