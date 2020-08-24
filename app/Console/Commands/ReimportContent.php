<?php

namespace App\Console\Commands;

use App\Models\Import;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReimportContent extends Command
{
    protected $signature = 'content:re-import {model : The name of the model you wish to re-import.}';
    protected $description = 'Sets the last imported date to 1970 for the specified model.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $model = $this->argument('model');

        $class = 'App\Models\\' . $model;

        if (! class_exists($class)) {
            $this->error('Class ' . $class . ' does not exist.');
            return;
        }

        $table_name = with(new Import())->getTable();

        $count = DB::table($table_name)->where('importable_type', $class)->update(['updated_at' => null]);

        $this->info($count . ' ' . Str::plural('item', $count) . ' set to be imported during the next content update.');
    }
}
