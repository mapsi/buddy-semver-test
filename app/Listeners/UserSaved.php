<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Log;
use App\Models\User;

class UserSaved
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->getUser();
        $orignal = $user->getOriginal();
        foreach (
            [
            'wtr_daily' => 'wtr',
            'wtr_weekly' => 'wtr',
            'iam_weekly' => 'iam'
            ] as $newsletter => $brand
        ) {
            if ((empty($orignal) && $user->$newsletter == 1) || (! empty($orignal) && isset($orignal[$newsletter]) && $user->$newsletter != $orignal[$newsletter])) {
                $l = new Log();
                $l->loggable_id = $user->id;
                $l->loggable_type = User::class;
                if (Auth()->user()) {
                    $l->user_id = Auth()->user()->id;
                    $l->user_name = Auth()->user()->email;
                } else {
                    $l->user_id = $user->id;
                    $l->user_name = $user->email;
                }
                $l->type = $user->$newsletter ? 'optin' : 'optout';
                $l->message = $newsletter;
                $l->save();
            }
        }
        //dd($event);
    }
}
