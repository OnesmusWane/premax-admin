<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Premax Admin sign-in code')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Use the code below to complete your sign-in. Do not share this code with anyone.')
            ->line('**' . $this->code . '**')
            ->line('This code expires in 10 minutes. If you did not request it, you can safely ignore this email.');
    }
}
