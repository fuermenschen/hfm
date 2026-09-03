<?php

use App\Actions\SendDonorInvoiceAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Mail\DonorInvoiceMail;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

function sendInvoiceFixture(array $overrides = []): DonorEventInvoice
{
    $event = DonationEvent::factory()->create(['ends_at' => now()->subDay()]);
    $donor = ExternalUser::factory()->create(['email' => 'donor@example.com']);
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create($overrides + [
        'webling_debitor_id' => 4321,
        'source_total_cents' => 1500,
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-test');

    return $invoice;
}

it('queues the invoice mail and stamps the sent time', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();

    app(SendDonorInvoiceAction::class)($invoice);

    $invoice->refresh();
    expect($invoice->invoice_sent_at)->not->toBeNull();
    Mail::assertQueued(DonorInvoiceMail::class, function (DonorInvoiceMail $mail) use ($invoice): bool {
        expect($mail->hasTo('donor@example.com'))->toBeTrue()
            ->and($mail->subject)->toBe('Rechnung Höhenmeter für Menschen')
            ->and($mail->storageAttachments[0]['disk'])->toBe('local')
            ->and($mail->storageAttachments[0]['path'])->toBe($invoice->pdf_path)
            ->and($mail->storageAttachments[0]['mime'])->toBe('application/pdf');

        return true;
    });
});

it('updates the sent time to the latest queue time on resend', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    $invoice->forceFill(['invoice_sent_at' => now()->subDays(3)])->save();
    $first = $invoice->refresh()->invoice_sent_at;

    app(SendDonorInvoiceAction::class)($invoice);

    expect($invoice->refresh()->invoice_sent_at)->toBeGreaterThan($first);
});

it('blocks sending before the event has ended', function (): void {
    Mail::fake();
    $event = DonationEvent::factory()->create(['ends_at' => now()->addDay()]);
    $invoice = sendInvoiceFixture();
    $invoice->forceFill(['donation_event_id' => $event->id])->save();

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'Rechnungen können erst nach Anlassende versendet werden.');
    Mail::assertNothingQueued();
    expect($invoice->refresh()->invoice_sent_at)->toBeNull();
});

it('blocks sending without a valid donor email', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    $invoice->externalUser->update(['email' => '']);

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'E-Mail-Adresse');
    Mail::assertNothingQueued();
});

it('blocks sending when the cached pdf file is missing', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    Storage::disk('local')->delete($invoice->pdf_path);

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'PDF-Datei');
    Mail::assertNothingQueued();
});

it('blocks sending without a debitor', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    $invoice->forceFill(['webling_debitor_id' => null])->save();

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'noch nicht in Webling erstellt');
});

it('blocks sending a remotely deleted invoice', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    $invoice->forceFill(['remote_deleted_at' => now()])->save();

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'gelöscht');
});

it('blocks sending an invoice with unknown webling state', function (): void {
    Mail::fake();
    $invoice = sendInvoiceFixture();
    $invoice->forceFill(['webling_state' => 'shredded'])->save();

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'unbekannt');
});

it('blocks sending paid and written-off invoices', function (string $state): void {
    Mail::fake();
    $invoice = sendInvoiceFixture(['webling_state' => $state]);

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'können nicht gesendet werden');
    Mail::assertNothingQueued();
})->with(['paid', 'writeoff']);

it('does not stamp the sent time when mail dispatch fails', function (): void {
    $invoice = sendInvoiceFixture();

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('queue unavailable'));

    expect(fn () => app(SendDonorInvoiceAction::class)($invoice))->toThrow(RuntimeException::class, 'queue unavailable');
    expect($invoice->refresh()->invoice_sent_at)->toBeNull();
});
