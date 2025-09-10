<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\DonorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoice implements ShouldQueue
{
    use Queueable;

    private $donorService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Donator $donor
    ) {
        $this->donorService = app(DonorService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // load invoice data
        $invoiceData = $this->donorService->collectInvoiceData($this->donor);

        dump('Test123');
    }
}
