<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorRecoveryCodeNotification extends Notification
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
            ->subject('Your Premax Admin 2FA recovery code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use the code below to recover access to your two-factor authentication setup.')
            ->line('Recovery code: '.$this->code)
            ->line('This code expires in 10 minutes.');
    }
}
