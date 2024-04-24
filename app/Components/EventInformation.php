<?php

namespace App\Components;

use Livewire\Component;

class EventInformation extends Component
{
    public function render()
    {
        return view("pages.event-information")->extends("layouts.public");
    }
}
