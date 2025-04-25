<?php

use App\Components\BecomeAthleteForm;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

test('renders successfully', function () {
    Livewire::test(BecomeAthleteForm::class)
        ->assertStatus(200);
});
