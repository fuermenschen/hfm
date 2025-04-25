<?php

use App\Components\BecomeDonatorForm;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

test('renders successfully', function () {
    Livewire::test(BecomeDonatorForm::class)
        ->assertStatus(200);
});
