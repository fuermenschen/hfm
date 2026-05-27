<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationRegistered extends Notification
{
    use Queueable;

    // TODO(refactor-external-user): Rewire notification dispatch from donation-created event on external-user flow.

    /**
     * Create a new notification instance.
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function __construct(
        public readonly string $first_name,
        public readonly string $athlete_name,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Anmeldung als Spender:in für '.$this->athlete_name)
            ->greeting('Hallo '.$this->first_name)
            ->line(sprintf('Du hast dich als Spender:in für %s angemeldet.', $this->athlete_name))
            ->line('Du kannst deinen Zugang jederzeit über den Login-Bereich anfordern:')
            ->action('Zum Login', route('login'))
            ->line('Vielen Dank für deine Unterstützung!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
