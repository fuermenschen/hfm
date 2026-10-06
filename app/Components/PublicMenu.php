<?php

declare(strict_types=1);

namespace App\Components;

use App\Services\CurrentDonationEventService;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PublicMenu extends Component
{
    #[Locked]
    public array $menuItems = [
        [
            'name' => 'Startseite',
            'route' => 'home',
            'active' => false,
        ],
        [
            'name' => 'Fragen und Antworten',
            'route' => 'questions-and-answers',
            'active' => false,
        ],
        [
            'name' => 'Sportler:in werden',
            'route' => 'become-athlete',
            'active' => false,
        ],
        [
            'name' => 'Spender:in werden',
            'route' => 'become-donor',
            'active' => false,
        ],
    ];

    public function mount(): void
    {
        $event = resolve(CurrentDonationEventService::class)->current();
        $this->menuItems = array_values(array_filter(
            $this->menuItems,
            fn (array $menuItem): bool => match ($menuItem['route']) {
                'become-athlete' => $event?->athleteRegistrationIsOpen() ?? false,
                'become-donor' => $event?->donorRegistrationIsOpen() ?? false,
                default => true,
            },
        ));

        $currentRoute = Route::currentRouteName();

        foreach ($this->menuItems as $key => $menuItem) {
            if ($menuItem['route'] === $currentRoute) {
                $this->menuItems[$key]['active'] = true;
            }
        }
    }

    public function render(): View
    {
        return view('components.public-menu');
    }
}
