<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeAthlete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeAthleteTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeAthlete::class)
            ->assertStatus(200);
    }
}
