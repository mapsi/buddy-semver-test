<?php

namespace App\Models\Observers;

use App\Models\Email;

class EmailObserver
{
    /**
     * Adding version number automatically for backwards compatibility
     *
     * @param Email $email
     */
    public function saving(Email $email)
    {
        if ($email->isDirty('content')) {
            $content = $email->content;
            $content['version'] = Email::LAST_VERSION;

            $email->content = $content;
        }
    }
}
