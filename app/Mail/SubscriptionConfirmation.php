<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $subscription, $brand, $renewal = false)
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->brand = $brand;
        $this->renewal = $renewal;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject('Welcome to your ' . $this->brand->name . ' subscription!');
        return $this->view('emails.subscriptionconfirmation')->with('user', $this->user)
            ->with('brand', $this->brand)->with('subscription', $this->subscription)
            ->with('renewal', $this->renewal);
    }
}
