<?php

namespace Tests\Feature\Livewire;

use App\Components\About;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class AboutTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(About::class)
            ->assertStatus(200);
    }
}
