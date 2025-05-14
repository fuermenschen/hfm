<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssociationDonationMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $pdf,
        public readonly string $filename,
    ) {
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
        $message->subject('Deine Spendenrechnung');
        $message->greeting('Hallo '.$this->name.',');
        $message->line('Vielen Dank, dass du uns unterstützen möchtest!');
        $message->line('Im Anhang findest du eine Spendenrechung.');
        $message->attachData(base64_decode($this->pdf), $this->filename);
        $message->bcc(config('mail.from.address'));

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
