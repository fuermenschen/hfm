<?php

use App\Components\PublicMenu;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(PublicMenu::class)
        ->assertStatus(200);
});

test('does not accept client mutations to menu items', function (): void {
    expect(fn () => Livewire::test(PublicMenu::class)->set('menuItems.0', 1))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
