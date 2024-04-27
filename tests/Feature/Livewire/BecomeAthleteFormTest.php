<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeAthleteForm;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeAthleteFormTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeAthleteForm::class)
            ->assertStatus(200);
    }
}
