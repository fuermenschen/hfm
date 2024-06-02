<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormMessage extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $email,
                                public readonly string $name,
                                public readonly string $message,
                                public readonly bool   $confirmation_to_sender = false,)
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
        $message = new MailMessage();

        if ($this->confirmation_to_sender) {
            $message->subject('Deine Kontaktanfrage');
            $message->greeting('Hallo ' . $this->name . ',');
            $message->line('Vielen Dank für deine Nachricht. Wir werden uns so schnell wie möglich bei dir melden.');
            $message->line('Deine Nachricht:');
            $message->line($this->message);
        } else {
            $message->replyTo($this->email, $this->name);
            $message->subject('Neue Kontaktanfrage');
            $message->greeting('Hallo,');
            $message->line('Es wurde eine neue Kontaktanfrage gestellt.');
            $message->line('Name: ' . $this->name);
            $message->line('E-Mail: ' . $this->email);
            $message->line('Nachricht:');
            $message->line($this->message);
        }

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
}
