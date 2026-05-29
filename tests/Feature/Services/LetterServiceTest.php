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
        dump($intro, $outro);
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
