<?php

use App\Components\PublicMenu;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

test('renders successfully', function () {
    Livewire::test(PublicMenu::class)
        ->assertStatus(200);
});
