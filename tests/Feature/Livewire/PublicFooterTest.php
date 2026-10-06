<?php

use function Pest\Laravel\get;

test('renders footer without a Livewire component', function (): void {
    get(route('home'))
        ->assertSee('Kontakt')
        ->assertSee('Newsletter')
        ->assertDontSee('footerItems');
});
