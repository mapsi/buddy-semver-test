<?php

namespace App\Newsletter;

use App\Models\Email;
use Illuminate\Contracts\Mail\Mailable;

interface INewsletter
{
    /**
     * @return string
     */
    public static function getFriendlyName();

    /**
     * @param Email    $email
     * @param Mailable $mailable
     * @return mixed
     */
    public function create(Email $email, Mailable $mailable);

    /**
     * @param Email    $email
     * @param Mailable $mailable
     * @return mixed
     */
    public function dispatch(Email $email, Mailable $mailable);

    /**
     * @param $campaignId
     * @param $date
     * @return mixed
     */
    public function schedule($campaignId, $date);
}
