<?php

namespace App\Components;

use Livewire\Component;

class Contact extends Component
{
    public function render()
    {
        return view("pages.contact")->extends("layouts.public");
    }
}
