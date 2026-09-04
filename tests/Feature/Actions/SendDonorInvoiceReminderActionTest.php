<?php

use App\Actions\SendDonorInvoiceReminderAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Mail\DonorInvoiceMail;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

function reminderInvoiceFixture(array $overrides = []): DonorEventInvoice
{
    $event = DonationEvent::factory()->create(['ends_at' => now()->subDay()]);
    $donor = ExternalUser::factory()->create(['email' => 'donor@example.com']);
    $invoice = DonorEventInvoice::factory()->forEvent($event)->forExternalUser($donor)->create($overrides + [
        'webling_debitor_id' => 4321,
        'source_total_cents' => 1500,
        'invoice_sent_at' => now()->subDays(14),
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-test');

    return $invoice;
}

function reminderWeblingMock(array $details, int $times = 1): WeblingInvoiceService
{
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->times($times)->andReturn($details);

    return $webling;
}

it('queues the reminder for an overdue open invoice and stamps the time', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = reminderWeblingMock(['state' => 'open', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500]);

    app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh();
    expect($invoice->invoice_reminder_sent_at)->not->toBeNull();
    Mail::assertQueued(DonorInvoiceMail::class, function (DonorInvoiceMail $mail): bool {
        expect($mail->hasTo('donor@example.com'))->toBeTrue()
            ->and($mail->subject)->toBe('Erinnerung: Rechnung Höhenmeter für Menschen')
            ->and($mail->body)->toContain('Herzlichen Dank für deine Unterstützung')
            ->and($mail->body)->toContain("Menschen.\n\nUnsere Rechnung")
            ->and($mail->body)->toContain('kannst du diese Erinnerung ignorieren');

        return true;
    });
});

it('queues the reminder for an overdue partially paid invoice', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = reminderWeblingMock(['state' => 'partially paid', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 500]);

    app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->refresh()->invoice_reminder_sent_at)->not->toBeNull();
});

it('updates the reminder time to the latest queue time on resend', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $invoice->forceFill(['invoice_reminder_sent_at' => now()->subDays(3)])->save();
    $first = $invoice->refresh()->invoice_reminder_sent_at;
    $webling = reminderWeblingMock(['state' => 'open', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500]);

    app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->refresh()->invoice_reminder_sent_at)->toBeGreaterThan($first);
});

it('blocks reminders for invoices that were never sent', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $invoice->forceFill(['invoice_sent_at' => null])->save();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('invoiceDetails');

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'noch nicht versendet');
    Mail::assertNothingQueued();
});

it('blocks reminders for paid and written-off invoices', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = reminderWeblingMock(['state' => 'paid', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 0]);

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'bereits bezahlt oder abgeschrieben');
    Mail::assertNothingQueued();
    expect($invoice->refresh()->invoice_reminder_sent_at)->toBeNull();
});

it('blocks reminders for unknown live webling states', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = reminderWeblingMock(['state' => 'shredded', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500]);

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'unbekannt');
});

it('blocks reminders that are not yet due', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = reminderWeblingMock(['state' => 'open', 'due_date' => today()->addWeek()->toDateString(), 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500]);

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'noch nicht fällig');
    expect($invoice->refresh()->invoice_reminder_sent_at)->toBeNull();
});

it('fails closed when webling is unavailable', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $response = new Response(new GuzzleHttp\Psr7\Response(503));
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(new WeblingApiException($response, WeblingApiException::Transient));

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(WeblingApiException::class);
    Mail::assertNothingQueued();
    expect($invoice->refresh()->invoice_reminder_sent_at)->toBeNull();
});

it('marks a missing remote invoice deleted and skips its reminder', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(404)), WeblingApiException::NotFound));

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'gelöscht');
    Mail::assertNothingQueued();
    expect($invoice->refresh()->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull();
});

it('blocks reminders without a valid donor email', function (): void {
    Mail::fake();
    $invoice = reminderInvoiceFixture();
    $invoice->externalUser->update(['email' => '']);
    $webling = reminderWeblingMock(['state' => 'open', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500]);

    expect(fn () => app(SendDonorInvoiceReminderAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'E-Mail-Adresse');
});
