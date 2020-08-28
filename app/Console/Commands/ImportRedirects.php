<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportRedirects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:redirects
                            {brand : Brand code}
                            {path=null : Path to redirects file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports brand redirects from CSV';

    protected $brand;

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
        $this->brand = $this->argument('brand');

        if ($this->brand == 'gcr' || $this->brand == 'grr') {
            $data = $this->readFile();

            if (is_array($data)) {
                $this->populateDatabase($data);
            }
        }
    }

    private function readFile()
    {
        //     "path_from": "",
        //     "path_to": "",

        // REMEBER TO SET encoding on csv to utf-8

        if ($this->argument('path') != 'null') {
            $file = file_get_contents($this->argument('path'));
        } else {
            $file = file_get_contents('https://lbrcdn.net/cdn/files/redirects.csv');
        }
        $data = str_getcsv($file, "\n");

        $csv = [];

        foreach ($data as $row) {
            $csv[] = str_getcsv($row, ",");
        }

        // use first row to create keys for associative array
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });

        // remove header
        array_shift($csv);

        return $csv;
    }

    private function populateDatabase($data)
    {
        $brandClass = Brand::where('machine_name', $this->brand)->first();

        $counter = 1;
        foreach ($data as $entry) {
            if (($entry['path_from']) && ($entry['path_to'])) {
                DB::table('redirects_v2')->insert(
                    [
                        'old' => $entry['path_from'],
                        'new' => $entry['path_to'],
                        'code' => 301,
                        'brand_id' => $brandClass->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $counter++;
            }
        }
        $this->info($counter . ' redirects created');
    }
}
