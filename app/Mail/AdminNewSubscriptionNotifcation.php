<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Coupon;

class AdminNewSubscriptionNotifcation extends Mailable
{
    use Queueable;
    use SerializesModels;

    protected $user;
    protected $team;
    protected $coupon;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Subscription $subscription, Coupon $coupon = null)
    {
        $this->user  = $user;
        $this->subscription = $subscription;
        $this->coupon = $coupon;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $v = $this->view('emails.admin.newpayment')->with('user', $this->user)->with('subscription', $this->subscription);
        if ($this->coupon) {
            $v->with('coupon', $this->coupon->code);
        }
        return $v;
    }
}
