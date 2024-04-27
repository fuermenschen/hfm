<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeAthleteForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeAthleteTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeAthleteForm::class)
            ->assertStatus(200);
    }
}
