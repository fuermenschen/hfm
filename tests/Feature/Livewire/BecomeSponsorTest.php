<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeSponsorForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeSponsorTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeSponsorForm::class)
            ->assertStatus(200);
    }
}
