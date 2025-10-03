<?php

use App\Models\Donator;

it('shows Webling link when debitor_url is present', function () {
    $donor = new Donator([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'email' => 'anna@example.com',
    ]);
    $donor->webling_data = [
        'debitor_url' => 'https://webling.example.com/admin#/accounting/1/debitor/:debitor/editor/123',
    ];
    $donor->login_token = 'test-token-123';

    $html = view('powergrid.admin-donor-actions', ['row' => $donor])->render();

    expect($html)->toContain('Rechnung in Webling anzeigen');
    expect($html)->toContain('https://webling.example.com/admin#/accounting/1/debitor/:debitor/editor/123');
});

it('does not show Webling link when debitor_url is missing', function () {
    $donor = new Donator([
        'first_name' => 'Max',
        'last_name' => 'Meier',
        'email' => 'max@example.com',
    ]);
    $donor->webling_data = [];
    $donor->login_token = 'test-token-456';

    $html = view('powergrid.admin-donor-actions', ['row' => $donor])->render();

    expect($html)->not()->toContain('Rechnung in Webling anzeigen');
});

it('shows reminder action when invoice is sent, overdue and not yet reminded', function () {
    $donor = new Donator([
        'first_name' => 'Lena',
        'last_name' => 'Beispiel',
        'email' => 'lena@example.com',
    ]);
    $donor->webling_data = [
        'payment_status' => 'overdue',
        'letter_pdf' => [
            'path' => 'letters/test.pdf',
        ],
    ];
    $donor->invoice_sent_at = now()->subDays(5);
    $donor->invoice_reminder_sent_at = null;
    $donor->login_token = 'test-token-reminder-1';

    $html = view('powergrid.admin-donor-actions', ['row' => $donor])->render();

    expect($html)->toContain('Zahlungserinnerung senden');
});

it('shows reminder action even when already reminded (single action allows resend with confirm)', function () {
    $donor = new Donator([
        'first_name' => 'Lukas',
        'last_name' => 'Beispiel',
        'email' => 'lukas@example.com',
    ]);
    $donor->webling_data = [
        'payment_status' => 'overdue',
        'letter_pdf' => [
            'path' => 'letters/test.pdf',
        ],
    ];
    $donor->invoice_sent_at = now()->subDays(5);
    $donor->invoice_reminder_sent_at = now()->subDay();
    $donor->login_token = 'test-token-reminder-2';

    $html = view('powergrid.admin-donor-actions', ['row' => $donor])->render();

    expect($html)->toContain('Zahlungserinnerung senden');
});
