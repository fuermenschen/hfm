<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class PublicMenu extends Component
{
    /**
     * @var array<int, array{name: string, route: string, active: bool}>
     */
    public array $menuItems;

    public function __construct(CurrentDonationEventService $currentDonationEventService)
    {
        $menuItems = [
            [
                'name' => 'Startseite',
                'route' => 'home',
            ],
            [
                'name' => 'Fragen und Antworten',
                'route' => 'questions-and-answers',
            ],
            [
                'name' => 'Sportler:in werden',
                'route' => 'become-athlete',
            ],
            [
                'name' => 'Spender:in werden',
                'route' => 'become-donor',
            ],
        ];

        $event = $currentDonationEventService->current();
        $menuItems = array_filter(
            $menuItems,
            fn (array $menuItem): bool => match ($menuItem['route']) {
                'become-athlete' => $event?->athleteRegistrationIsOpen() ?? false,
                'become-donor' => $event?->donorRegistrationIsOpen() ?? false,
                default => true,
            },
        );

        $currentRoute = Route::currentRouteName();

        $this->menuItems = array_map(
            fn (array $menuItem): array => [...$menuItem, 'active' => $menuItem['route'] === $currentRoute || $currentRoute === $menuItem['route'].'.show'],
            array_values($menuItems),
        );
    }

    public function render(): View
    {
        return view('components.public-menu');
    }
}
