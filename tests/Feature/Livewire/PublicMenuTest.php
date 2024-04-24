<?php

namespace Tests\Feature\Livewire;

use App\Components\PublicMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(PublicMenu::class)
            ->assertStatus(200);
    }
}
