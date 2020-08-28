<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Contact extends Mailable
{
    use Queueable;
    use SerializesModels;

    protected $data;
    protected $title;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $title = 'SUBSCRIPTION REQUEST', $template = 'emails.admin.subscription', $additional_data = [])
    {
        $this->data = $data;
        $this->title = $title;
        $this->template = $template;
        $this->additional_data = $additional_data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view($this->template)->with('data', $this->data)->with('title', $this->title)->with('additional_data', $this->additional_data);
    }
}
