<?php

namespace App\Components;

use Illuminate\Support\Facades\Request;
use Illuminate\View\View;
use Livewire\Component;

class PublicMenu extends Component
{
    public $menuItems = [
        [
            "name" => "Startseite",
            "route" => "home",
            "active" => false,
        ],
        [
            "name" => "Fragen und Antworten",
            "route" => "questions-and-answers",
            "active" => false,
        ],
        [
            "name" => "Sportler:in werden",
            "route" => "become-athlete",
            "active" => false,
        ],
        [
            "name" => "Spender:in werden",
            "route" => "become-donator",
            "active" => false,
        ],
    ];

    public function mount(): void
    {
        $currentRoute = Request::route()->getName();

        foreach ($this->menuItems as $key => $menuItem) {
            if ($menuItem["route"] === $currentRoute) {
                $this->menuItems[$key]["active"] = true;
            }
        }
    }

    public function render(): View
    {
        return view("components.public-menu");
    }
}
