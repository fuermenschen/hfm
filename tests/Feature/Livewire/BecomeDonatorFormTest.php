<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeDonatorForm;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BecomeDonatorFormTest extends TestCase
{
    #[Test] public function renders_successfully()
    {
        Livewire::test(BecomeDonatorForm::class)
            ->assertStatus(200);
    }
}
