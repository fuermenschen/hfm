<?php

namespace Tests\Feature\Livewire;

use App\Components\PublicFooter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PublicFooterTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(PublicFooter::class)
            ->assertStatus(200);
    }
}
