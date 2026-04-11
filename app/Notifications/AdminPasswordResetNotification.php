<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $resetUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your Premax Admin password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A password reset was requested for your admin account.')
            ->action('Reset Password', $this->resetUrl)
            ->line('If you did not request this, you can ignore this email.');
    }
}
