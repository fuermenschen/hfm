<?php

use App\Jobs\CreateDonorInvoiceLetter;
use App\Models\Donator;
use App\Services\Webling\Letter\LetterService;
use Illuminate\Http\Client\Response;

it('passes properly configured QrInvoiceOptions with debtor details to the letter service', function (): void {
    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'first_name' => 'Clara',
        'last_name' => 'Klein',
        'address' => 'Musterweg 5',
        'zip_code' => '8001',
        'city' => 'Zürich',
    ]);

    // Pretend the debitor already exists
    $donator->webling_data = ['debitor_id' => 456];
    $donator->save();

    // Mock LetterService to capture and assert the QrInvoiceOptions
    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(false); // avoid file IO in job
    $letterResponse->shouldReceive('status')->andReturn(123);
    $letterResponse->shouldReceive('body')->andReturn('Simulated failure');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')
        ->once()
        ->withArgs(function (string $title, callable $configure, int $debitorId) use ($donator): bool {
            expect($title)->toBe('Spendenrechnung Höhenmeter für Menschen')
                ->and($debitorId)->toBe(456);

            // Build a draft using the provided configure callback
            $builder = new \App\Services\Webling\Letter\LetterBuilder;
            $configure($builder);
            $draft = $builder->build();

            $qr = $draft->qr?->toArray() ?? [];

            expect($qr['debtorName'] ?? null)->toBe([
                $donator->first_name.' '.$donator->last_name,
            ])
                ->and($qr['debtorAddress1'] ?? null)->toBe([
                    $donator->address,
                ])
                ->and($qr['debtorAddress2'] ?? null)->toBe([
                    $donator->zip_code.' '.$donator->city,
                ]);

            // We do not force withAmount here; it should come from settings fallback
            return true;
        })
        ->andReturn($letterResponse);

    app()->instance(LetterService::class, $letterService);

    // Run the job
    (new CreateDonorInvoiceLetter($donator))->handle();
});
