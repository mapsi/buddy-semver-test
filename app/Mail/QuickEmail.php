<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class QuickEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $view;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($view)
    {
        $this->view = $view;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->view($this->view);
    }
}
