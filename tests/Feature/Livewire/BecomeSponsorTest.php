<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeSponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeSponsorTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeSponsor::class)
            ->assertStatus(200);
    }
}
