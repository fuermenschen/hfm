<?php

namespace Tests\Feature\Livewire;

use App\Components\Privacy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(Privacy::class)
            ->assertStatus(200);
    }
}
