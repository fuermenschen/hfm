<?php

use App\Components\BecomeAthleteForm;
use Livewire\Livewire;

test('renders successfully', function () {
    Livewire::test(BecomeAthleteForm::class)
        ->assertStatus(200);
})->skip('Registrierung aktuell geschlossen.');
