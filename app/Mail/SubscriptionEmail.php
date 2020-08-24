<?php

namespace App\Mail;

use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @var array
     */
    public $email;

    /**
     * @var bool
     */
    protected $isReview;

    /**
     * @param array $email
     */
    public function __construct(array $email)
    {
        $this->email = $email;
    }

    /**
     * @return $this
     */
    public function build()
    {
        $this->from($this->email['address_from'], $this->email['name_from']);
        $this->subject($this->email['subject']);
        if ($this->isReview) {
            $this->subject('Please review: ' . $this->subject);
        }

        $emailView = "emails.{$this->email['machine_name']}.{$this->email['type']}";
        $this->view($emailView, ['email' => $this->email]);

        return $this;
    }

    /**
     * @param bool $isReview
     * @return $this
     */
    public function setReview($isReview = true)
    {
        $this->isReview = $isReview;

        return $this;
    }
}
