<?php

use App\Components\PublicFooter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(PublicFooter::class)
        ->assertStatus(200);
});

test('does not accept client mutations to footer items', function (): void {
    expect(fn () => Livewire::test(PublicFooter::class)->set('footerItems.0', 1))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
