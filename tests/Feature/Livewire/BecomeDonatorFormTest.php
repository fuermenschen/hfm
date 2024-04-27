<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeDonatorForm;
use Livewire\Livewire;
use Tests\TestCase;

class BecomeDonatorFormTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        Livewire::test(BecomeDonatorForm::class)
            ->assertStatus(200);
    }
}
