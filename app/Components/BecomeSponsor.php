<?php

namespace App\Components;

use Livewire\Component;

class BecomeSponsor extends Component
{
    public function render()
    {
        return view("pages.become-sponsor")->extends("layouts.public");
    }
}
