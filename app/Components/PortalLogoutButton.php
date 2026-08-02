<?php

declare(strict_types=1);

namespace App\Components;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Livewire\Component;

class PortalLogoutButton extends Component
{
    public function logout(Request $request): void
    {
        auth()->guard('external')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->redirectRoute('home', navigate: true);
    }

    public function render(): Factory|View
    {
        return view('components.portal-logout-button');
    }
}
