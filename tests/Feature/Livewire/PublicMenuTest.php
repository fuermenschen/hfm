<?php

use App\Components\PublicMenu;
use Illuminate\Support\Facades\Route;
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

test('marks the FAQ menu item active on an edition-specific FAQ page', function (): void {
    Route::partialMock()->shouldReceive('currentRouteName')->andReturn('questions-and-answers.show');
    $menu = new PublicMenu;

    $menu->mount();

    expect($menu->menuItems[1]['active'])->toBeTrue();
});
