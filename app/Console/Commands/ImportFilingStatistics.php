<?php

namespace App\Console\Commands;

use App\Models\Directory;
use App\Models\FilingStatisticsEntry;
use App\Models\FilingStatisticsJurisdiction;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;

class ImportFilingStatistics extends Command
{
    protected $signature = 'filing-statistics:import';
    protected $description = 'Imports the filing statistics.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new GuzzleClient([
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $response = $client->get('http://oldsite.worldtrademarkreview.com/api2/directory/wtr1000/filingstatistics');

        $data = json_decode($response->getBody(), true);

        foreach ($data as $filing_statistic) {
            $directory = Directory::where('slug', $filing_statistic['product'])->firstOrFail();

            $jurisdiction = FilingStatisticsJurisdiction::firstOrNew(['name' => $filing_statistic['group']]);

            if (! $jurisdiction->exists) {
                $directory->filingStatistics()->save($jurisdiction);
            }

            $entry = FilingStatisticsEntry::firstOrNew(['uuid' => $filing_statistic['rankingGuid']], [
                'firm_name' => $filing_statistic['firmName'],
                'person_name' => $filing_statistic['name'] ?: null,
                'rank' => $filing_statistic['rank'],
            ]);

            if (! $entry->exists) {
                $jurisdiction->entries()->save($entry);
            }
        }

        $this->info('Done!');
    }
}
