<?php

use App\Components\AdminWeblingInterfaceTest;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\LetterService;
use App\Settings\WeblingApiSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => str_repeat('a', 32),
        'accounting_period_id' => 10,
        'debit_account_id' => 100,
        'credit_account_id' => 200,
    ]);

    Storage::fake('local');
});

it('renders successfully in intro step', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->assertStatus(200)
        ->assertSet('step', 'intro')
        ->assertSet('debitorId', null)
        ->assertSet('debitorUrl', null)
        ->assertSet('tempPdfPath', null);
});

it('has test data pre-generated on mount', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->assertNotSet('testData', []);
});

it('generates new test data when restartWizard is called', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('restartWizard')
        ->assertSet('step', 'intro')
        ->assertSet('debitorId', null);
});

it('transitions to inspect_pdf on successful start', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(201);
    $debitorResponse->shouldReceive('json')->andReturn(42);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(true);
    $letterResponse->shouldReceive('body')->andReturn('%PDF-1.4 fake pdf content');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('step', 'inspect_pdf')
        ->assertSet('debitorId', 42)
        ->assertNotSet('debitorUrl', null)
        ->assertNotSet('tempPdfPath', null);
});

it('stores the debitor URL with the correct format', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(201);
    $debitorResponse->shouldReceive('json')->andReturn(99);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(true);
    $letterResponse->shouldReceive('body')->andReturn('%PDF fake');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('debitorUrl', 'https://demo.webling.ch/admin#/accounting/10/debitor/:debitor/editor/99');
});

it('saves pdf to local storage on success', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(201);
    $debitorResponse->shouldReceive('json')->andReturn(7);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    $pdfContent = '%PDF-1.4 test content';

    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(true);
    $letterResponse->shouldReceive('body')->andReturn($pdfContent);

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start');

    $path = $component->get('tempPdfPath');
    expect($path)->not->toBeNull();
    Storage::disk('local')->assertExists($path);
});

it('transitions to error state when debitor creation returns non-201', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(500);
    $debitorResponse->shouldReceive('body')->andReturn('Internal Server Error');

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('step', 'error')
        ->assertSet('errorStep', 'debitor_creation')
        ->assertNotSet('errorMessage', null);
});

it('transitions to error state when debitor creation throws an exception', function (): void {
    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andThrow(new Exception('Connection refused'));
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('step', 'error')
        ->assertSet('errorStep', 'debitor_creation');
});

it('transitions to error state when letter creation fails', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(201);
    $debitorResponse->shouldReceive('json')->andReturn(55);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(false);
    $letterResponse->shouldReceive('status')->andReturn(422);
    $letterResponse->shouldReceive('body')->andReturn('Validation error');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('step', 'error')
        ->assertSet('errorStep', 'letter_creation')
        ->assertSet('debitorId', 55);
});

it('transitions to error when letter response body is empty', function (): void {
    $debitorResponse = Mockery::mock(Response::class);
    $debitorResponse->shouldReceive('status')->andReturn(201);
    $debitorResponse->shouldReceive('json')->andReturn(42);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('createInvoiceFromParams')->once()->andReturn($debitorResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    $letterResponse = Mockery::mock(Response::class);
    $letterResponse->shouldReceive('successful')->andReturn(true);
    $letterResponse->shouldReceive('body')->andReturn('');

    $letterService = Mockery::mock(LetterService::class);
    $letterService->shouldReceive('createInvoiceLetter')->once()->andReturn($letterResponse);
    app()->instance(LetterService::class, $letterService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->call('start')
        ->assertSet('step', 'error')
        ->assertSet('errorStep', 'letter_creation')
        ->assertSet('debitorId', 42);
});

it('transitions from inspect_pdf to inspect_link when all checklist items are decided', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf')
        ->set('debitorId', 42)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => true,
            'amount_correct' => true,
            'qr_present' => true,
            'date_correct' => true,
        ])
        ->call('confirmPdf')
        ->assertSet('step', 'inspect_link');
});

it('allows advancing from inspect_pdf even when some checklist items are marked as failed', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf')
        ->set('debitorId', 42)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => false,
            'amount_correct' => true,
            'qr_present' => false,
            'date_correct' => true,
        ])
        ->call('confirmPdf')
        ->assertSet('step', 'inspect_link');
});

it('does not advance from inspect_pdf when any checklist item is undecided', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf')
        ->set('debitorId', 42)
        ->call('confirmPdf')
        ->assertSet('step', 'inspect_pdf');
});

it('logs checklist failures when advancing from inspect_pdf', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg, $ctx) => str_contains($msg, 'PDF checklist has failures') && in_array('address_correct', $ctx['failed_items']));

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf')
        ->set('debitorId', 42)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => false,
            'amount_correct' => true,
            'qr_present' => true,
            'date_correct' => true,
        ])
        ->call('confirmPdf');
});

it('transitions to done after successful cleanup via confirmLink with ok result', function (): void {
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('successful')->andReturn(true);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->with(42)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Storage::disk('local')->put('webling/test-uuid.pdf', 'pdf content');

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_link')
        ->set('debitorId', 42)
        ->set('tempPdfPath', 'webling/test-uuid.pdf')
        ->call('confirmLink', true)
        ->assertSet('step', 'done')
        ->assertSet('debitorId', null)
        ->assertSet('tempPdfPath', null)
        ->assertSet('linkCheckResult', true);

    Storage::disk('local')->assertMissing('webling/test-uuid.pdf');
});

it('transitions to done with link failure recorded when confirmLink called with false', function (): void {
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('successful')->andReturn(true);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_link')
        ->set('debitorId', 42)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => true,
            'amount_correct' => true,
            'qr_present' => true,
            'date_correct' => true,
        ])
        ->call('confirmLink', false)
        ->assertSet('step', 'done')
        ->assertSet('linkCheckResult', false);
});

it('transitions to error state when cleanup deletion fails', function (): void {
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('successful')->andReturn(false);
    $deleteResponse->shouldReceive('status')->andReturn(500);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_link')
        ->set('debitorId', 42)
        ->call('confirmLink', true)
        ->assertSet('step', 'error')
        ->assertSet('errorStep', 'cleanup');
});

it('still deletes local pdf even when debitor deletion fails', function (): void {
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('successful')->andReturn(false);
    $deleteResponse->shouldReceive('status')->andReturn(500);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Storage::disk('local')->put('webling/test-cleanup.pdf', 'pdf content');

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_link')
        ->set('debitorId', 42)
        ->set('tempPdfPath', 'webling/test-cleanup.pdf')
        ->call('confirmLink', true);

    Storage::disk('local')->assertMissing('webling/test-cleanup.pdf');
});

it('cleans up debitor from error state via runCleanup', function (): void {
    $deleteResponse = Mockery::mock(Response::class);
    $deleteResponse->shouldReceive('successful')->andReturn(true);

    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->with(99)->andReturn($deleteResponse);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'error')
        ->set('errorStep', 'letter_creation')
        ->set('debitorId', 99)
        ->call('runCleanup')
        ->assertSet('step', 'done')
        ->assertSet('debitorId', null);
});

it('resets to intro step with fresh test data on restartWizard', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'done')
        ->set('debitorId', 42)
        ->set('errorMessage', 'some error')
        ->call('restartWizard')
        ->assertSet('step', 'intro')
        ->assertSet('debitorId', null)
        ->assertSet('errorMessage', null);
});

it('handleModalCancel cleans up debitor and resets wizard when test is in progress', function (): void {
    $invoiceService = Mockery::mock(WeblingInvoiceService::class);
    $invoiceService->shouldReceive('deleteInvoice')->once()->with(77);
    app()->instance(WeblingInvoiceService::class, $invoiceService);

    Storage::disk('local')->put('webling/test-mid.pdf', 'pdf content');

    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf')
        ->set('debitorId', 77)
        ->set('tempPdfPath', 'webling/test-mid.pdf')
        ->call('handleModalCancel')
        ->assertSet('step', 'intro')
        ->assertSet('debitorId', null);

    Storage::disk('local')->assertMissing('webling/test-mid.pdf');
});

it('handleModalCancel simply resets when in intro step', function (): void {
    Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'intro')
        ->call('handleModalCancel')
        ->assertSet('step', 'intro')
        ->assertSet('debitorId', null);
});

it('returns progress value 0 for intro step', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'intro');

    expect($component->instance()->getProgressProperty())->toBe(0);
});

it('returns progress value 50 for inspect_pdf step', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'inspect_pdf');

    expect($component->instance()->getProgressProperty())->toBe(50);
});

it('returns progress value 100 for done step', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('step', 'done');

    expect($component->instance()->getProgressProperty())->toBe(100);
});

it('checklist is not decided until all items are set to true or false', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class);

    expect($component->instance()->getChecklistDecidedProperty())->toBeFalse();

    $component->set('checklist', [
        'name_correct' => true,
        'address_correct' => false,
        'amount_correct' => true,
        'qr_present' => true,
        'date_correct' => false,
    ]);

    expect($component->instance()->getChecklistDecidedProperty())->toBeTrue();
});

it('checklist has failures when any item is false', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => false,
            'amount_correct' => true,
            'qr_present' => true,
            'date_correct' => true,
        ]);

    expect($component->instance()->getChecklistHasFailuresProperty())->toBeTrue();
});

it('checklist has no failures when all items are true', function (): void {
    $component = Livewire::test(AdminWeblingInterfaceTest::class)
        ->set('checklist', [
            'name_correct' => true,
            'address_correct' => true,
            'amount_correct' => true,
            'qr_present' => true,
            'date_correct' => true,
        ]);

    expect($component->instance()->getChecklistHasFailuresProperty())->toBeFalse();
});
