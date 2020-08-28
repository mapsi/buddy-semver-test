<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $resetUrl = url(config('app.url') . route('password.reset', ['token' => $this->token], false));
        $siteUrl = url(config('app.url'));
        $contactEmail = resolve(\App\Models\Brand::class)->contact_info['email'];

        $linkExpires = \Carbon\Carbon::now()->addHour(72)->toDateTimeString();
        $expiryTime = date('H:i:s', strtotime($linkExpires));
        $expiryDate = date('l \t\h\e jS \o\f F', strtotime($linkExpires));

        return (new MailMessage())->markdown('auth.emails.password', compact('resetUrl', 'siteUrl', 'contactEmail', 'expiryTime', 'expiryDate'));
    }
}
