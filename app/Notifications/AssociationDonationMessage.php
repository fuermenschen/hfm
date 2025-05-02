<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssociationDonationMessage extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $email,
        public readonly string $name,
        public readonly array $details,
        public readonly bool $confirmation_to_sender = false, )
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
        $message = new MailMessage;

        if ($this->confirmation_to_sender) {
            $message->subject('Deine Spende');
            $message->greeting('Hallo '.$this->name.',');
            $message->line('Vielen Dank, dass du uns unterstützen möchtest!');
            $message->line('Wir stellen dir demnächst eine Spendenrechnung per E-Mail zu.');
            $message->line('Hier sind die Angaben, die du uns übermittelt hast:');
            $message->lines($this->details);
        } else {
            $message->replyTo($this->email, $this->name);
            $message->subject('Neue Spendenanfrage');
            $message->greeting('Hallo,');
            $message->line('Es wurde eine neue Spendenanfrage gestellt.');
            $message->line('Name: '.$this->name);
            $message->line('E-Mail: '.$this->email);
            $message->line('Angaben zur Rechnung:');
            $message->lines($this->details);
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
