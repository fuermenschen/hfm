<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\GetDashboardDataAction;
use App\Models\DonationEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AdminDashboardContent extends Component
{
    #[Locked]
    public ?string $eventSlug = null;

    protected GetDashboardDataAction $getDashboardData;

    public function boot(GetDashboardDataAction $getDashboardData): void
    {
        $this->getDashboardData = $getDashboardData;
    }

    public function mount(?string $eventSlug = null): void
    {
        $this->eventSlug = $eventSlug;
    }

    public function render(): View
    {
        abort_unless(Auth::guard('web')->check(), 403);

        $event = $this->eventSlug === null
            ? null
            : DonationEvent::query()->where('slug', $this->eventSlug)->firstOrFail();

        return view('components.admin-dashboard-content', ($this->getDashboardData)($event));
    }

    public function placeholder(): View
    {
        return view('components.admin-dashboard-content-placeholder');
    }
}
