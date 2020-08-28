<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Export;

class RunExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:runexports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pickup an export that is pending and process it.';

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
//
        $export = Export::
            where('status', '=', 'new')->
            first();
        if ($export) {
            $export->process();
        }
    }
}
