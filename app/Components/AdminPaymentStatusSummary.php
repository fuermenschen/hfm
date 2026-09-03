<?php

declare(strict_types=1);

namespace App\Components;

use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AdminPaymentStatusSummary extends Component
{
    public int $paid = 0;

    public int $overdue = 0;

    public int $sent = 0;

    public int $created = 0;

    public int $notCreated = 0;

    public int $partiallyPaid = 0;

    public int $writeoff = 0;

    public int $remoteDeleted = 0;

    public int $unknown = 0;

    public function render(): Factory|View
    {
        return view('components.admin.payment-status-summary');
    }

    /**
     * Receive summary from the donor table component and open the modal.
     *
     * @param  array{paid?:int,overdue?:int,sent?:int,created?:int,not_created?:int,partially_paid?:int,writeoff?:int,remote_deleted?:int,unknown?:int}  $summary
     */
    #[On('showPaymentStatusSummary')]
    public function open(array $summary): void
    {
        $this->paid = (int) ($summary['paid'] ?? 0);
        $this->overdue = (int) ($summary['overdue'] ?? 0);
        $this->sent = (int) ($summary['sent'] ?? 0);
        $this->created = (int) ($summary['created'] ?? 0);
        $this->notCreated = (int) ($summary['not_created'] ?? 0);
        $this->partiallyPaid = (int) ($summary['partially_paid'] ?? 0);
        $this->writeoff = (int) ($summary['writeoff'] ?? 0);
        $this->remoteDeleted = (int) ($summary['remote_deleted'] ?? 0);
        $this->unknown = (int) ($summary['unknown'] ?? 0);

        Flux::modal('payment-status-summary')->show();
    }
}
