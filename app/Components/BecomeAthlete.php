<?php

namespace App\Components;

use Livewire\Component;

class BecomeAthlete extends Component
{
    public function render()
    {
        return view("pages.become-athlete")->extends("layouts.public");
    }
}
