<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use DateTime;

class SendEmails implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $email;
    protected $last = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return false;//dont use
        //keep going till your done
        set_time_limit(0);


        $recipients_query = User::query()
        ->receievesEmail($this->email->brand, $this->email->type)
        ->whereDoesntHave('emailsReceived', function ($query) {
            $query->where('emails.id', $this->email->id);
        })
        ->when(
            $this->email->brand->machineNameIs('wtr') && $this->email->type === 'daily',
            function ($query) {
                $query->subscribesToBrand($this->email->brand);
            }
        )
        ->limit(100);

        while (count($recipients = $recipients_query->get())) {
            Mail::to($recipients)->send($this->email->getMailable());
            foreach ($recipients as $recipient) {
                $recipient->emailsReceived()->syncWithoutDetaching([$this->email->id]);
            }
        }
    }
}
