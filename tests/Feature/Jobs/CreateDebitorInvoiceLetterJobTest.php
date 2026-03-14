<?php

use App\Jobs\CreateDonorInvoiceLetter;
use App\Models\Donor;
use App\Services\Webling\Letter\LetterBuilder;
use App\Services\Webling\Letter\LetterService;
use Illuminate\Http\Client\Response;

it('passes properly configured QrInvoiceOptions with debtor details to the letter service', function (): void {
    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'first_name' => 'Clara',
        'last_name' => 'Klein',
        'address' => 'Musterweg 5',
        'zip_code' => '8001',
        'city' => 'Zürich',
    ]);

    // Pretend the debitor already exists
    $donor->webling_data = ['debitor_id' => 456];
    $donor->save();

    // Mock LetterService to capture and assert the QrInvoiceOptions
    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(false); // avoid file IO in job
    $letterResponse->shouldReceive('status')->andReturn(123);
    $letterResponse->shouldReceive('body')->andReturn('Simulated failure');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')
        ->once()
        ->withArgs(function (string $title, callable $configure, int $debitorId) use ($donor): bool {
            expect($title)->toBe('Spendenrechnung Höhenmeter für Menschen')
                ->and($debitorId)->toBe(456);

            // Build a draft using the provided configure callback
            $builder = new LetterBuilder;
            $configure($builder);
            $draft = $builder->build();

            $qr = $draft->qr?->toArray() ?? [];

            expect($qr['debtorName'] ?? null)->toBe([
                $donor->first_name.' '.$donor->last_name,
            ])
                ->and($qr['debtorAddress1'] ?? null)->toBe([
                    $donor->address,
                ])
                ->and($qr['debtorAddress2'] ?? null)->toBe([
                    $donor->zip_code.' '.$donor->city,
                ]);

            // We do not force withAmount here; it should come from settings fallback
            return true;
        })
        ->andReturn($letterResponse);

    app()->instance(LetterService::class, $letterService);

    // Run the job
    (new CreateDonorInvoiceLetter($donor))->handle();
});
