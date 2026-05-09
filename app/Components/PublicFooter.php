<?php

declare(strict_types=1);

namespace App\Components;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
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
        [
            'name' => 'Newsletter',
            'route' => 'newsletter',
        ],
    ];

    public function render(): Factory|View
    {
        return view('components.public-footer');
    }
}
