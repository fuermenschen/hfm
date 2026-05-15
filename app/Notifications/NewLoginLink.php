<?php

declare(strict_types=1);

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
        public readonly string $donor_login_token = '',
        public readonly string $user_login_url = '',
        public readonly string $external_user_login_url = '', )
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

        $loginLinks = $this->loginLinks();

        if (count($loginLinks) === 1) {
            $message->line('Du hast deinen Anmelde-Link angefordert. Bitte klicke auf den unten stehenden Button, um dich anzumelden.');
            $message->action('Login', $loginLinks[0]['url']);
        } else {
            $message->line('Du hast mehrere Rollen. Bitte klicke unten auf den entsprechenden Link, um dich anzumelden.');

            foreach ($loginLinks as $loginLink) {
                $message->line($loginLink['label'].': '.$loginLink['url']);
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
     * @return array<int, array{label: string, url: string}>
     */
    protected function loginLinks(): array
    {
        $links = [];

        if ($this->athlete_login_token !== '') {
            $links[] = [
                'label' => 'Anmelden als Sportler:in',
                'url' => route('show-athlete', $this->athlete_login_token),
            ];
        }

        if ($this->donor_login_token !== '') {
            $links[] = [
                'label' => 'Anmelden als Spender:in',
                'url' => route('show-donor', $this->donor_login_token),
            ];
        }

        if ($this->user_login_url !== '') {
            $links[] = [
                'label' => 'Anmelden als Benutzer:in',
                'url' => $this->user_login_url,
            ];
        }

        if ($this->external_user_login_url !== '') {
            $links[] = [
                'label' => 'Anmelden im Portal',
                'url' => $this->external_user_login_url,
            ];
        }

        return $links;
    }
}
