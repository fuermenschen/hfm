<?php

namespace App\Components;

use Livewire\Component;

class Privacy extends Component
{
    public function render()
    {
        return view("pages.privacy")->extends("layouts.public");
    }
}
