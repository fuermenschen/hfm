<?php

namespace App\Components;

use App\Models\Athlete;
use Livewire\Component;

class Home extends Component
{
    public $athleteCount = 0;

    public function mount()
    {
        $this->athleteCount = Athlete::count();
    }

    public function render(): \Illuminate\View\View
    {
        return view("home")->extends("layouts.public");
    }
}
