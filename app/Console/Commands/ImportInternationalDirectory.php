<?php

namespace App\Console\Commands;

use App\Models\InternationalDirectoryEntry;
use App\Models\InternationalDirectoryJurisdiction;
use DateTime;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Illuminate\Support\Str;

class ImportInternationalDirectory extends Command
{
    protected $signature = 'international-directory:import {path=storage/app/international_directory.xml : The path to the XML file.}';
    protected $description = 'Loads the XML file into the DB.';

    public function handle()
    {
        $directory = simplexml_load_file($this->argument('path'));
        $entries = [];
        $jurisdictions = [];
        foreach ($directory as $entry) {
            $jurisdiction_model = InternationalDirectoryJurisdiction::firstOrCreate(['name' => $entry['Country']]);
            $jurisdictions[] = $entry['Country'];
            $entry_model = InternationalDirectoryEntry::firstOrNew(['uuid' => $entry['DirectoryGuid']]);
            $entries[] = (string) $entry['DirectoryGuid'];
            $entry_model->fill([
                'name' => $entry['FirmName'],
                'description' => $entry['Description'],
                'address' => ! empty($entry['Address']) ? strip_tags($entry['Address']) : null,
                'phone' => $entry['Telephone'],
                'fax' => ! empty($entry['Fax']) ? $entry['Fax'] : null,
                'email' => ! empty($entry['Email']) ? $entry['Email'] : null,
                'website' => 'http://' . $entry['Website'],
                // We're not importing the featured dates because they have values like 2100-01-01T00:00:00 which makes their utility questionable.
            ]);

            $jurisdiction_model->entries()->save($entry_model);

            try {
                $entry_model->clearMediaCollection('logo');
                if ($entry['LogoUrl']) {
                    $this->info($entry['LogoUrl']);
                    $entry_model->addMediaFromUrl($entry['LogoUrl'])->toMediaCollection('logo');
                }
            } catch (FileCannotBeAdded $exception) {
                $this->error('Could not fetch the logo for ' . $entry_model->name . ': ' . $exception->getMessage());
            }
        }

        $jurisdiction_count = InternationalDirectoryJurisdiction::count();
        $entry_count = InternationalDirectoryEntry::count();

        $this->info('Imported ' . $entry_count . ' ' . Str::plural('entry', $entry_count) . ' into ' . $jurisdiction_count . ' ' . Str::plural('jurisdiction', $jurisdiction_count) . '.');

        InternationalDirectoryEntry::whereNotIn('uuid', $entries)->get()->each(function ($it) {
            $this->info('Removing ' . $it->name . ' (' . $it->uuid . ')');
            $it->delete();//clean up media
        });
        InternationalDirectoryJurisdiction::whereNotIn('name', $jurisdictions)->get()->each(function ($it) {
            $this->info('Removing ' . $it->name . ' (' . $it->id . ')');
            $it->delete();//clean up media
        });
    }
}
