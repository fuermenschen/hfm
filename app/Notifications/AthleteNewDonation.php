<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AthleteNewDonation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $first_name,
                                public readonly string $donator_name,
                                public readonly string $login_token)
    {
        //
    }

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
            ->subject("Ein:e Spender:in hat sich für dich registriert!")
            ->greeting("Hallo $this->first_name,")
            ->line("Soeben hat sich $this->donator_name als Spender:in für dich registriert.")
            ->line("Wenn du dich einloggst, siehst du, wer alles für dich spenden wird.")
            ->action('Login', route('show-athlete', $this->login_token))
            ->line('Vielen Dank, dass du so fleissig mithilfst, spenden zu sammeln!');
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
