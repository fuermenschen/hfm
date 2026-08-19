<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AthleteRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AthleteRegistrationReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $athleteRegistrationId,
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
        return AthleteRegistration::query()
            ->whereKey($this->athleteRegistrationId)
            ->where('verified', false)
            ->exists();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Erinnerung: Bitte bestätige deine Sportler:innen-Anmeldung')
            ->greeting('Hallo '.$this->firstName)
            ->line('Bitte bestätige deine Anmeldung als Sportler:in im Portal.')
            ->line('Mit der Bestätigung stellen wir sicher, dass die Anmeldung wirklich von dir stammt. Ohne Bestätigung können Spender:innen dich nicht auswählen.')
            ->action('Zum Portal', route('portal.dashboard'))
            ->line('Falls du diese Anmeldung nicht gestartet hast, kontaktiere uns bitte.');
    }
}
