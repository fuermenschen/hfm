<?php

use App\Components\PublicFooter;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(PublicFooter::class)
        ->assertStatus(200);
});
