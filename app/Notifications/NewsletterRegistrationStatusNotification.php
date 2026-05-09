<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewsletterRegistrationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $firstName, public readonly bool $alreadyRegistered) {}

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
            ->subject('Newsletter-Anmeldung')
            ->greeting('Hallo '.$this->firstName);

        if ($this->alreadyRegistered) {
            return $message
                ->line('Danke, dass du dich für unseren Newsletter eingetragen hast.')
                ->line('Deine E-Mail-Adresse war bereits für unseren Newsletter registriert.')
                ->line('Sobald es Neuigkeiten gibt, bekommst du eine Nachricht von uns.')
                ->line('Bei Fragen kannst du jederzeit auf diese Nachricht antworten.');
        }

        $recipientEmail = $notifiable instanceof AnonymousNotifiable
            ? (string) $notifiable->routeNotificationFor('mail')
            : (string) ($notifiable->email ?? '');

        $unsubscribeUrl = URL::temporarySignedRoute(
            'newsletter.unsubscribe',
            now()->addDay(),
            ['email' => $recipientEmail]
        );

        return $message
            ->line('Danke, dass du dich für unseren Newsletter eingetragen hast.')
            ->line('In diesem Newsletter informieren wir über wichtige Neuigkeiten rund um den Anlass "Höhenmeter für Menschen".')
            ->line('Bei Fragen kannst du jederzeit auf diese Nachricht antworten.')
            ->line('')
            ->line('Falls du dich aus Versehen registriert hast oder jemand deine E-Mail-Adresse genutzt hat, kannst du dich mit dem folgenden Link wieder abmelden.')
            ->action('Newsletter-Abmeldung', $unsubscribeUrl);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'first_name' => $this->firstName,
            'already_registered' => $this->alreadyRegistered,
        ];
    }
}
