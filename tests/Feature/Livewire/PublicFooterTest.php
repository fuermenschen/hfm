<?php

namespace Tests\Feature\Livewire;

use App\Components\PublicFooter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFooterTest extends TestCase
{
    #[Test] public function renders_successfully()
    {
        Livewire::test(PublicFooter::class)
            ->assertStatus(200);
    }
}
