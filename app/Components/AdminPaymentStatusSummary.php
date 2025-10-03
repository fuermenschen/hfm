<?php

namespace App\Components;

use Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class AdminPaymentStatusSummary extends Component
{
    public int $paid = 0;

    public int $overdue = 0;

    public int $sent = 0;

    public int $created = 0;

    public int $notCreated = 0;

    public function render()
    {
        return view('components.admin.payment-status-summary');
    }

    /**
     * Receive summary from the donor table component and open the modal.
     *
     * @param  array{paid:int,overdue:int,sent:int,created:int,not_created:int}  $summary
     */
    #[On('showPaymentStatusSummary')]
    public function open(array $summary): void
    {
        $this->paid = (int) ($summary['paid']);
        $this->overdue = (int) ($summary['overdue']);
        $this->sent = (int) ($summary['sent']);
        $this->created = (int) ($summary['created']);
        $this->notCreated = (int) ($summary['not_created']);

        Flux::modal('payment-status-summary')->show();
    }
}
