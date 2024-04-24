<?php

namespace Tests\Feature\Livewire;

use App\Components\Impressum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class ImpressumTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(Impressum::class)
            ->assertStatus(200);
    }
}
