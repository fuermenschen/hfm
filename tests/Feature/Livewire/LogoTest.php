<?php

namespace Tests\Feature\Livewire;

use App\Components\Logo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class LogoTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(Logo::class)
            ->assertStatus(200);
    }
}
