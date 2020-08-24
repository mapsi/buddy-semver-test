<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:passwords
                            {brand : Brand code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports brand passwords from CSV';

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

        if ($this->brand == 'gcr' || $this->brand == 'gir') {
            $data = $this->readFile();

            if (is_array($data)) {
                $this->populateDatabase($data);
            }
        }
    }

    private function readFile()
    {
        // headers reference
        // [
        //     {
        //     "user_name": "",
        //     "email_id": "xxx@xxx.org",
        //     "password": "",
        //     "site_code": "gir"
        //     },
        // ]

        //$file = Storage::disk('local')->get('subscribers_details.csv');
        $file = file_get_contents('https://lbrcdn.net/cdn/files/subscribers_details.csv');
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
            if (
                (strtoupper($entry['site_code']) == strtoupper($this->brand))
                && ($entry['password']) && ($entry['email_id'])
            ) {
                //check if user exists
                $user = User::where('email', $entry['email_id'])->first();

                if ($user) {
                    // update brand user password
                    $query = DB::table('brand_user')
                    ->where('brand_id', $brandClass->id)
                    ->where('user_id', $user->id);

                    $brandUser = $query->first();

                    if ($brandUser) {
                        $query->update([
                            'legacy_password_hash' => $entry['password'],
                            'updated_at' => \Carbon\Carbon::now(),
                        ]);
                        $counter++;
                        $this->info('user ' . $user->id . ' updated');
                    }
                }
            }
        }
        $this->info($counter . ' passwords updated');
    }
}
