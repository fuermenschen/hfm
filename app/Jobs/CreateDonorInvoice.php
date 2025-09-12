<?php

namespace App\Jobs;

use App\Models\Donator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class CreateDonorInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donator $donor) {}

    public function handle(): void
    {
        // Orchestrate sequentially via the bus: first create debitor, then generate the letter PDF
        Bus::chain([
            new CreateDonorInvoiceDebitor($this->donor),
            new CreateDebitorInvoiceLetter($this->donor),
        ])->dispatch();
    }
}
