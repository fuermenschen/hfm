<?php

namespace Tests\Feature\Livewire;

use App\Components\EventInformation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class EventInformationTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(EventInformation::class)
            ->assertStatus(200);
    }
}
