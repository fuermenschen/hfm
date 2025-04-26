<?php

use App\Components\BecomeMemberForm;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(BecomeMemberForm::class)
        ->assertStatus(200);
});
