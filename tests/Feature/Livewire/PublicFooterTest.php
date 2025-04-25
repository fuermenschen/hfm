<?php

use App\Components\PublicFooter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

test('renders successfully', function () {
    Livewire::test(PublicFooter::class)
        ->assertStatus(200);
});
