<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AthleteRegistered extends Notification
{
    use Queueable;

    // TODO(refactor-athlete-relaunch): Wire this notification back into the external-user athlete registration flow.
    public string $first_name = '';

    public string $public_id_string = '';

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
            ->subject('Deine Registrierung als Sportler:in')
            ->greeting('Hallo '.$this->first_name)
            ->line('Vielen Dank für deine Registrierung bei uns. Bitte klicke auf den unten stehenden Button, um dich einzuloggen.')
            ->action('Zum Login', route('login'))
            ->line('Sobald du deine E-Mail-Adresse bestätigt hast, können deine Sponsor:innen dich auswählen. Gib ihnen dafür einfach deinen Namen und den unten stehenden Code.')
            ->line('Code: '.$this->public_id_string)
            ->line('Übrigens: Du kannst jederzeit nachsehen, wer dich bereits unterstützt. Klicke dafür einfach auf den Button oben.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
