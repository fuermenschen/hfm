<?php

namespace App\Components;

use Livewire\Component;

class Impressum extends Component
{
    public function render()
    {
        return view("pages.impressum")->extends("layouts.public");
    }
}
