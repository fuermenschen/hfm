<?php

namespace Tests\Feature\Livewire;

use App\Components\PublicMenu;
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
