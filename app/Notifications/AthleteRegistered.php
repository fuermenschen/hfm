<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AthleteRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $first_name,
        public readonly string $public_id_string,
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
            ->subject('Deine Registrierung als Sportler:in')
            ->greeting('Hallo '.$this->first_name)
            ->line('Vielen Dank für deine Registrierung bei uns. Bitte klicke auf den unten stehenden Button, um deine E-Mail-Adresse zu bestätigen.')
            ->action('Anmeldung bestätigen', route('show-athlete', $this->login_token))
            ->line('Sobald du deine E-Mail-Adresse bestätigt hast, können deine Sponsor:innen dich auswählen. Gib ihnen dafür einfach deinen Namen und den unten stehenden Code.')
            ->line('Code: '.$this->public_id_string)
            ->line('Übrigens: Du kannst jederzeit nachsehen, wer dich bereits unterstützt. Klicke dafür einfach auf den Button oben.');
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
