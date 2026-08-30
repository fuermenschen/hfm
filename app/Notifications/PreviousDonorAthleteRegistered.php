<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreviousDonorAthleteRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $athletePrivacyName,
        public readonly string $donationEventTitle,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->athletePrivacyName.' ist wieder dabei')
            ->greeting('Hallo '.$this->firstName)
            ->line($this->athletePrivacyName.' hat sich für '.$this->donationEventTitle.' als Sportler:in angemeldet.')
            ->line('Du hast diese Person früher schon unterstützt. Vielleicht möchtest du auch dieses Jahr wieder dabei sein.')
            ->action('Sportler:in unterstützen', route('become-donor'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
