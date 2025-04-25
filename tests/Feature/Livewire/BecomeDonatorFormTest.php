<?php

use App\Components\BecomeDonatorForm;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(BecomeDonatorForm::class)
        ->assertStatus(200);
});
