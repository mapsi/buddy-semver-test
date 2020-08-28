<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Newsletter;

class NewsletterPopulate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'populate the newsletter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $users = \App\Models\User::chunk(500, function ($list) {
            $api = Newsletter::getApi();

            $batch = $api->new_batch();

            foreach ($list as $user) {
                $intr = $user->newsletterInterests();
                $batch->put('op' . $user->id, 'lists/' . config('newsletter.lists.subscribers.id') . '/members/' . $api->subscriberHash($user->email), [
                    'email_address' => $user->email,

                    'merge_fields' => [
                        'FNAME' => $user->forename,
                        'LNAME' => $user->surname
                    ],
                    'interests' => $intr
                ] + (collect($intr)->filter(function ($item) {
                    return $item;
                })->count() ? [ 'status' => 'subscribed' ] : []));
            }
            $d = $batch->execute();
            $this->info('Imported batch ID: ' . $d['id']);
        });
    }
}
