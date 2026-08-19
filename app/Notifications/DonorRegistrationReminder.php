<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonorRegistrationReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $donationId,
        public readonly string $firstName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return Donation::query()
            ->whereKey($this->donationId)
            ->where('verified', false)
            ->exists();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Erinnerung: Bitte bestätige deine Spende')
            ->greeting('Hallo '.$this->firstName)
            ->line('Bitte bestätige deine Spende im Portal.')
            ->line('Mit der Bestätigung stellen wir sicher, dass die Spende wirklich von dir stammt. So verhindern wir, dass eine Rechnung an jemanden geschickt wird, der nicht spenden wollte.')
            ->action('Zum Portal', route('portal.dashboard'))
            ->line('Erst nach der Bestätigung wird deine Spende aktiv.')
            ->line('Falls du diese Spende nicht gestartet hast, kontaktiere uns bitte.');
    }
}
