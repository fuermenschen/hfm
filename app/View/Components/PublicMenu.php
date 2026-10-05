<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\DonationEvent;
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

        if (! $currentDonationEventService->current() instanceof DonationEvent) {
            $menuItems = array_filter(
                $menuItems,
                fn (array $menuItem): bool => ! in_array($menuItem['route'], ['become-athlete', 'become-donor'], true),
            );
        }

        $currentRoute = Route::currentRouteName();

        $this->menuItems = array_map(
            fn (array $menuItem): array => [...$menuItem, 'active' => $menuItem['route'] === $currentRoute],
            $menuItems,
        );
    }

    public function render(): View
    {
        return view('components.public-menu');
    }
}
