<?php

declare(strict_types=1);

namespace App\Components;

use App\Support\AdminFiles\AdminFileStorage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminNormalizeSvgFiles extends Component
{
    /**
     * @var array{scanned:int,normalized:int,unchanged:int,failed:array<int, array{path:string,message:string}>}|null
     */
    public ?array $result = null;

    public function normalize(): void
    {
        abort_unless(Auth::check(), 403);

        $this->result = $this->files()->normalizeSvgFiles();

        Flux::toast(
            heading: 'SVGs normalisiert',
            text: $this->result['normalized'].' von '.$this->result['scanned'].' SVGs angepasst.',
            variant: $this->result['failed'] === [] ? 'success' : 'warning',
        );
    }

    public function render(): View
    {
        return view('components.admin-normalize-svg-files');
    }

    protected function files(): AdminFileStorage
    {
        return resolve(AdminFileStorage::class);
    }
}
