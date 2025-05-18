<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoginLink extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $first_name,
        public readonly string $athlete_login_token = '',
        public readonly string $donator_login_token = '',
        public readonly string $user_login_url = '', )
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

        $message = (new MailMessage)
            ->subject('Neuer Anmelde-Link')
            ->greeting('Hallo '.$this->first_name);

        if (! $this->hasMultipleLoginTokens()) {
            $message->line('Du hast deinen Anmelde-Link angefordert. Bitte klicke auf den unten stehenden Button, um dich anzumelden.');
            if ($this->athlete_login_token !== '') {
                $message->action('Login', route('show-athlete', $this->athlete_login_token));
            }
            if ($this->donator_login_token !== '') {
                $message->action('Login', route('show-donator', $this->donator_login_token));
            }
            if ($this->user_login_url !== '') {
                $message->action('Login', $this->user_login_url);
            }
        } else {
            $message->line('Du hast mehrere Rollen. Bitte klicke unten auf den entsprechenden Link, um dich anzumelden.');
            if ($this->athlete_login_token !== '') {
                $message->line('Anmelden als Sportler:in: '.route('show-athlete', $this->athlete_login_token));
            }
            if ($this->donator_login_token !== '') {
                $message->line('Anmelden als Spender:in: '.route('show-donator', $this->donator_login_token));
            }
            if ($this->user_login_url !== '') {
                $message->line('Anmelden als Benutzer:in: '.$this->user_login_url);
            }
        }

        $message->line('Falls du Probleme hast, melde dich bitte bei uns.');

        return $message;
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

    /**
     * Check if multiple login tokens are set (more than one login link).
     */
    public function hasMultipleLoginTokens(): bool
    {
        $num_tokens = 0;
        if ($this->athlete_login_token !== '') {
            $num_tokens++;
        }
        if ($this->donator_login_token !== '') {
            $num_tokens++;
        }
        if ($this->user_login_url !== '') {
            $num_tokens++;
        }

        return $num_tokens > 1;
    }
}
