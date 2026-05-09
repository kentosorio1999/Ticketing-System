<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendEmailVerificationCode extends Notification
{
    use Queueable;

    public function __construct(public string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('DICT VII - MISS Account Verification Code')
        ->greeting('Dear ' . $notifiable->name . ',')
        ->line('Greetings from the Department of Information and Communications Technology Region VII - MISS.')
        ->line('We received your account registration request for the DICT VII - MISS Service Desk System.')
        ->line('To continue verifying your account, please use the One-Time Password (OTP) below:')
        ->line($this->code)
        ->line('This OTP is valid for 10 minutes only. For your security, please do not share this code with anyone.')
        ->line('If you did not initiate this registration request, please disregard this email.')
        ->salutation('Respectfully,' . "\n" . 'DICT VII - MISS Service Desk Team');
}
}