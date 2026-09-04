<?php

use App\Services\Webling\Letter\Dto\QrInvoiceOptions;
use App\Services\Webling\Letter\LetterApiClient;
use App\Services\Webling\Letter\LetterBuilder;
use App\Services\Webling\Letter\LetterRenderer;
use App\Services\Webling\Letter\LetterSchemaValidator;
use App\Services\Webling\Letter\LetterService;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Illuminate\Http\Client\Response;

it('builds and posts a letter for a debitor', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $client = new LetterApiClient($api);
    $renderer = new LetterRenderer;
    $validator = new LetterSchemaValidator;

    $fakeResponse = Mockery::mock(Response::class);

    $api->shouldReceive('post')->once()->withArgs(function (string $path, array $payload): bool {
        expect($path)->toBe('letter/new/send')
            ->and($payload['properties']['title'])->toBe('Invoice Title')
            ->and($payload['properties']['state'])->toBe('sent')
            ->and($payload['properties']['lettertype'])->toBe('debitor')
            ->and($payload['links']['debitor'])->toBe([12345]);

        $data = json_decode($payload['properties']['data'], true);
        expect($data)->toBeArray()
            ->and($data['options'])->toBeArray()
            ->and($data['body'])->toBeArray();

        // Check our configured texts appear
        $bodyBlocks = $data['body'];
        $intro = $bodyBlocks[3][0]['content']['html'] ?? '';
        $outro = $bodyBlocks[5][0]['content']['html'] ?? '';
        expect($intro)->toContain('Liebe:r Anna')
            ->and($outro)->toContain('Bitte verwende zur');

        return true;
    })->andReturn($fakeResponse);

    $service = new LetterService($renderer, $validator, $client);

    $service->createInvoiceLetter('Invoice Title', function (LetterBuilder $b): void {
        $b->header("Höhenmeter\nfür Menschen")
            ->body1('Liebe:r Anna')
            ->body2('Bitte verwende zur')
            ->withQrInvoice(function (QrInvoiceOptions $q): void {
                $q->withAmount = false;
            });
    }, 12345);
});

it('renders persisted letter snapshots without reading current settings', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $response = Mockery::mock(Response::class);
    $api->shouldReceive('post')->once()->withArgs(function (string $path, array $payload): bool {
        $data = json_decode($payload['properties']['data'], true);

        expect($path)->toBe('letter/new/send')
            ->and($data['options']['showHeader'])->toBeFalse()
            ->and($data['qrInvoice']['iban'])->toBe('CH5604835012345678009')
            ->and($data['body'][3][0]['content']['html'])->toContain('Frozen intro')
            ->and($data['body'][5][0]['content']['html'])->toContain('Frozen outro')
            ->and($data['body'][1][0]['content']['html'])->toContain('24. März 2025')
            ->and($data['body'][1][0]['content']['html'])->not->toContain('{{D. MMMM YYYY}}');

        return true;
    })->andReturn($response);

    $snapshot = [
        'letter' => [
            'header_text' => 'Frozen header',
            'body_intro' => 'Frozen intro',
            'body_outro' => 'Frozen outro',
            'date' => '2025-03-24',
            'qr_invoice' => [
                'iban' => 'CH5604835012345678009',
                'creditorName' => 'Frozen creditor',
            ],
            'options' => [
                'showHeader' => false,
            ],
        ],
    ];

    $service = new LetterService(new LetterRenderer, new LetterSchemaValidator, new LetterApiClient($api));

    $service->createFromSnapshot($snapshot, 'Frozen invoice', 12345);
});
