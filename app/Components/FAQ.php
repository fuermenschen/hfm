<?php

namespace App\Components;

use Livewire\Component;

class FAQ extends Component
{
    public function render()
    {
        return view("pages.f-a-q")->extends("layouts.public");
    }
}
