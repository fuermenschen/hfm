<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $message,
        public readonly string $subject = '',
        public readonly string $first_name = '',
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
        $msg = new MailMessage;

        if ($this->subject !== '') {
            $msg->subject($this->subject);
        } else {
            $msg->subject('Nachricht von Höhenmeter für Menschen');
        }

        if ($this->first_name !== '') {
            $msg->greeting('Hallo '.$this->first_name);
        } else {
            $msg->greeting('Hallo');
        }

        $msg->line($this->message);

        return $msg;
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
