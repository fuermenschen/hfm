<?php

namespace App\Components;

use Livewire\Component;

class PublicFooter extends Component
{
    public $footerItems = [
        [
            'name' => 'Kontakt',
            'route' => 'contact',
        ],
        [
            'name' => 'Impressum',
            'route' => 'impressum',
        ],
        [
            'name' => 'Datenschutz',
            'route' => 'privacy',
        ],
        [
            'name' => 'Verein',
            'route' => 'association',
        ],
    ];

    public function render()
    {
        return view('components.public-footer');
    }
}
