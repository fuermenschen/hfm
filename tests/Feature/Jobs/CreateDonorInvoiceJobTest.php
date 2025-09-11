<?php

use App\Jobs\CreateDonorInvoice;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\LetterService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;

it('creates a letter after creating a donor invoice and stores flags and pdf handle', function (): void {
    // Prepare donor with one donation line
    /** @var Donator $donator */
    $donator = Donator::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);

    // Ensure related partner and sport type exist to satisfy FKs
    $partner = Partner::create(['name' => 'AC Test']);
    $sportType = SportType::create(['name' => 'Trail']);

    /** @var Athlete $athlete */
    $athlete = Athlete::factory()->verified()->create([
        'rounds_done' => 5,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);

    /** @var Donation $donation */
    $donation = Donation::create([
        'donator_id' => $donator->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 10.0,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => 'Thanks!',
    ]);

    // Fake storage for local disk
    Storage::fake('local');

    // Mock Webling invoice creation response
    $invoiceResponse = Mockery::mock(Response::class);
    $invoiceResponse->shouldReceive('status')->andReturn(201);
    $invoiceResponse->shouldReceive('json')->andReturn(98765);

    // Bind WeblingInvoiceService mock
    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoice')->once()->andReturn($invoiceResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    // Mock LetterService to return successful response with PDF body
    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(true);
    $letterResponse->shouldReceive('body')->andReturn('%PDF-1.4 fake-pdf-binary');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')
        ->once()
        ->withArgs(function (string $title, callable $configure, int $debitorId): bool {
            expect($title)->toBe('Spendenrechnung Höhenmeter für Menschen')
                ->and($debitorId)->toBe(98765);
            // Execute the configure closure to ensure it is callable
            $builder = new \App\Services\Webling\Letter\LetterBuilder;
            $configure($builder);

            return true;
        })
        ->andReturn($letterResponse);

    app()->instance(LetterService::class, $letterService);

    // Execute job synchronously
    (new CreateDonorInvoice($donator))->handle();

    // Assert webling_data contains debitor_id, letter_created flag, and the stored PDF handle
    $donator->refresh();
    expect($donator->webling_data['debitor_id'] ?? null)->toBe(98765)
        ->and($donator->webling_data['letter_created'] ?? null)->toBeTrue()
        ->and($donator->webling_data['letter_pdf']['disk'] ?? null)->toBe('local')
        ->and(isset($donator->webling_data['letter_pdf']['path']))->toBeTrue();

    // Assert file exists on fake disk
    Storage::disk('local')->assertExists($donator->webling_data['letter_pdf']['path']);
});

it('sets letter_created to false and no pdf handle when letter creation fails', function (): void {
    $donator = Donator::factory()->create([
        'first_name' => 'Ben',
        'last_name' => 'Beispiel',
    ]);

    $partner = Partner::create(['name' => 'AC Test']);
    $sportType = SportType::create(['name' => 'Trail']);

    $athlete = Athlete::factory()->verified()->create([
        'rounds_done' => 3,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);

    Donation::create([
        'donator_id' => $donator->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 5.0,
        'comment' => 'x',
    ]);

    // Fake storage
    Storage::fake('local');

    // Invoice response OK
    $invoiceResponse = Mockery::mock(Response::class);
    $invoiceResponse->shouldReceive('status')->andReturn(201);
    $invoiceResponse->shouldReceive('json')->andReturn(12345);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoice')->once()->andReturn($invoiceResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    // Letter service returns unsuccessful response
    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(false);

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    (new CreateDonorInvoice($donator))->handle();

    $donator->refresh();
    expect($donator->webling_data['debitor_id'] ?? null)->toBe(12345)
        ->and($donator->webling_data['letter_created'] ?? null)->toBeFalse()
        ->and(isset($donator->webling_data['letter_pdf']))->toBeFalse();
});
