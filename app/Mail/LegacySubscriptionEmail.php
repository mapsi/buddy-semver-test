<?php

namespace App\Mail;

use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class LegacySubscriptionEmail
 * This is used for v1 of the emails before the block integration
 * It's only used for preview, waiting for business decision to discontinue
 *
 * @package App\Mail
 */
class LegacySubscriptionEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $email;
    protected $is_review;

    /**
     * Create a new message instance.
     *
     * @param Email $email
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from($this->email->brand->email_from['address'], $this->email->brand->email_from['name']);

        $this->subject($this->email->subject);

        if ($this->is_review) {
            $this->subject('Please review: ' . $this->subject);
        }

        $this->view('emails.legacy.' . $this->email->brand->machine_name . '_' . $this->email->type, [
            'email' => $this->email,
        ]);

        return $this;
    }

    public function setReview($is_review = true)
    {
        $this->is_review = $is_review;

        return $this;
    }
}
