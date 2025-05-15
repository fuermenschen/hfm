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
        $message->subject('Deine Spendenrechnung')
            ->greeting('Hallo '.$this->name.',')
            ->line('Danke, dass du den Verein für Menschen finanziell unterstützen möchtest. Dank Spenden wie deiner können wir die Organisation von Spendenanlässen finanzieren. Herzlichen Dank.')
            ->line('Im Anhang findest du eine Spendenrechung.')
            ->attachData(base64_decode($this->pdf), $this->filename)
            ->bcc(config('mail.from.address'));

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
