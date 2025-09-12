<?php

namespace App\Jobs;

use App\Models\Donator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donator $donor) {}

    public function handle(): void
    {
        // Orchestrate: first ensure we have a debitor, then create the letter PDF
        (new CreateDonorInvoiceDebitor($this->donor))->handle();
        (new CreateDebitorInvoiceLetter($this->donor))->handle();
    }
}
