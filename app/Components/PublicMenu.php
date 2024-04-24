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
            "route" => "/",
            "active" => false,
        ],
        [
            "name" => "Über das Projekt",
            "route" => "ueber-das-projekt",
            "active" => false,
        ],
        [
            "name" => "Sportler:in werden",
            "route" => "sportlerin-werden",
            "active" => false,
        ],
        [
            "name" => "Sponsor:in werden",
            "route" => "sponsorin-werden",
            "active" => false,
        ],
        [
            "name" => "Informationen zum Anlass",
            "route" => "informationen-zum-anlass",
            "active" => false,
        ],
    ];

    public function mount(): void
    {
        $currentRoute = Request::path();

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
