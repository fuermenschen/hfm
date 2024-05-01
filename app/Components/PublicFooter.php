<?php

namespace App\Components;

use Livewire\Component;

class PublicFooter extends Component
{
    public $footerItems = [
        [
            "name" => "Häufige Fragen",
            "route" => "faq",
        ],
        [
            "name" => "Kontakt",
            "route" => "contact",
        ],
        [
            "name" => "Impressum",
            "route" => "impressum",
        ],
        [
            "name" => "Datenschutz",
            "route" => "privacy",
        ],
    ];

    public function render()
    {
        return view("components.public-footer");
    }
}
