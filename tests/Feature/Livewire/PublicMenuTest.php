<?php

namespace Tests\Feature\Livewire;

use App\Components\PublicMenu;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    #[Test] public function renders_successfully()
    {
        Livewire::test(PublicMenu::class)
            ->assertStatus(200);
    }
}
