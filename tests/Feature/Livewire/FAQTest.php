<?php

namespace Tests\Feature\Livewire;

use App\Components\FAQ;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class FAQTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(FAQ::class)
            ->assertStatus(200);
    }
}
