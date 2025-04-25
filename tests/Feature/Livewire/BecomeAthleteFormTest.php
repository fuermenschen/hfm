<?php

namespace Tests\Feature\Livewire;

use App\Components\BecomeAthleteForm;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BecomeAthleteFormTest extends TestCase
{
    #[Test] public function renders_successfully()
    {
        Livewire::test(BecomeAthleteForm::class)
            ->assertStatus(200);
    }
}
