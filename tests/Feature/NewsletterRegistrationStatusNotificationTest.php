<?php

use App\Notifications\NewsletterRegistrationStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

test('new registrations include a valid 24h signed unsubscribe link', function (): void {
    $frozenNow = Carbon::create(2026, 3, 12, 9, 30, 0);
    Carbon::setTestNow($frozenNow);

    $notifiable = (new AnonymousNotifiable)->route('mail', 'anna@example.com');

    try {
        $notification = new NewsletterRegistrationStatusNotification('Anna', false);
        $mailMessage = $notification->toMail($notifiable);

        $unsubscribeUrl = $mailMessage->actionUrl;
        $actionText = $mailMessage->actionText;

        expect($unsubscribeUrl)->toBeString()
            ->and($actionText)->toBe('Newsletter-Abmeldung');

        $unsubscribePath = (string) parse_url((string) $unsubscribeUrl, PHP_URL_PATH);
        expect(Str::contains($unsubscribePath, '/newsletter/abmelden/'))->toBeTrue()
            ->and(urldecode((string) basename($unsubscribePath)))->toBe('anna@example.com');

        $request = Request::create((string) $unsubscribeUrl);
        expect(URL::hasValidSignature($request))->toBeTrue();

        parse_str((string) parse_url((string) $unsubscribeUrl, PHP_URL_QUERY), $query);

        expect($query)->toHaveKey('expires')
            ->and((int) $query['expires'])->toBe($frozenNow->copy()->addDay()->timestamp);
    } finally {
        Carbon::setTestNow();
    }
});

test('already-registered addresses do not receive unsubscribe link', function (): void {
    $notifiable = (new AnonymousNotifiable)->route('mail', 'anna@example.com');

    $notification = new NewsletterRegistrationStatusNotification('Anna', true);
    $mailMessage = $notification->toMail($notifiable);
    expect($mailMessage->actionUrl)->toBeNull()
        ->and($mailMessage->actionText)->toBeNull();
});
