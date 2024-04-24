<?php

namespace App\Components;

use Livewire\Component;

class About extends Component
{
    public function render()
    {
        return view("pages.about")->extends("layouts.public");
    }
}
