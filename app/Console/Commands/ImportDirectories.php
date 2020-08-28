<?php

namespace App\Console\Commands;

use App\Classes\WtrDirectoryApi;
use App\Models\Directory;
use App\Models\DirectoryContact as Contact;
use App\Models\DirectoryEditorial as Editorial;
use App\Models\DirectoryFirm as Firm;
use App\Models\DirectoryIndividual as Individual;
use App\Models\DirectoryJurisdiction as Jurisdiction;
use App\Models\DirectoryProfile as Profile;
use App\Models\DirectoryRanking as Ranking;
use DOMDocument;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\UnreachableUrl;

class ImportDirectories extends Command
{
    protected $signature = 'directories:import
        {--directories= : Directories to import comma separated}
        {--delete-previous-data}';
    protected $description = 'Fetches the directory data.';

    public function handle()
    {
        $t = microtime(true);
        $directories = $this->getDirectoriesToImport();
        $this->deletePreviousData($directories);

        foreach ($directories as $directory_model) {
            $directory_api = new WtrDirectoryApi($directory_model->base_uri);

            /* Individuals */
            $this->info('Importing ' . $directory_model->name . ' individuals...');
            $sectors = collect();

            foreach ($directory_api->individuals() as $individual) {
                // Clean up array
                $associations = empty($individual['profile']['professionalAssociations']) ? null : array_map(function ($association) {
                    // Would have used array_column but one of the individuals uses the name field.
                    return $association['firmName'] ? $association['firmName'] : $association['name'];
                }, $individual['profile']['professionalAssociations']);

                // Make an HTML list into an array.
                $clients = ! empty($individual['profile']['sampleClients']) ? array_map('strip_tags', explode('</li><li>', $individual['profile']['sampleClients'])) : null;

                // Get photo or gender
                $gender = null;

                if ($path = parse_url($individual['photo'], PHP_URL_PATH)) {
                    $filename = pathinfo($path, PATHINFO_FILENAME);

                    if (strtolower($filename) === 'generic-male') {
                        $gender = 'male';
                    } else {
                        if (strtolower($filename) === 'generic-female') {
                            $gender = 'female';
                        }
                    }
                }

                $data = [
                    'first_names' => preg_replace('/(\s' . preg_quote($individual['surname'], '/') . '$)/', ' ', $individual['name']),
                    'surname' => $individual['surname'],
                    'gender' => $gender,
                    'firm_name' => $individual['firmName'],
                    'position' => ! empty($individual['title']) ? $individual['title'] : null,
                    'city' => ! empty($individual['city']) ? $individual['city'] : null,
                    'country' => $individual['country'],
                    'associations' => $associations,
                    'clients' => $clients,
                    //'sectors' => !empty($individual['profile']['sectors']) ? $individual['profile']['sectors'] : null,
                    'is_operating' => isset($individual['EntryType']) ? $individual['EntryType'] === 'Operating' : null,
                    'uuid' => $individual['id'],
                    'directory_id' => $directory_model->id,
                    'iamsays' => $individual['iamsays'] ?? null,
                ];

                $individual_model = Individual::updateOrCreate(
                    [
                        'uuid' => $individual['id'],
                        'directory_id' => $directory_model->id,
                    ],
                    $data
                );

                if (! $individual['hasProfile']) {
                    $individual_model->slug = $individual['id'];
                    $individual_model->save();
                }

                if ($gender === null && $individual['photo'] && $individual['photo'] && ! empty($individual['profile'])) {
                    try {
                        $individual_model->addMediaFromUrl($individual['photo'])->toMediaCollection('photo');
                    } catch (UnreachableUrl $exception) {
                        $this->output->error($exception->getMessage());
                    }
                } else {
                    $individual_model->clearMediaCollectionExcept('photo');
                }

                if (parse_url($individual['firmLogo'], PHP_URL_PATH)) { // Rather than say there is no logo, the path given is the brand's domain.
                    try {
                        $individual_model->addMediaFromUrl($individual['firmLogo'])->toMediaCollection('firm_logo');
                    } catch (UnreachableUrl $exception) {
                        $this->output->error($exception->getMessage());
                    }
                } else {
                    $individual_model->clearMediaCollectionExcept('firm_logo');
                }

                $directory_model->individuals()->save($individual_model);
                // Import sectors here
                $sectors = [];
                if (isset($individual['sectors']) && is_array($individual['sectors'])) {
                    $sectors = $individual['sectors'];
                }
                $individual_model->directorySectors()->detach();
                foreach ($sectors as $sector) {
                    //will slow things down
                    $sector = \App\Models\DirectorySector::firstOrCreate(['name' => $sector]);
                    $individual_model->directorySectors()->attach($sector->id);
                }
                if ($individual_model->profile) {
                    $individual_model->profile->delete();
                }
                if ($individual['profile'] && ! empty($individual['profile']['description'])) {
                    // Switch <br/> to line breaks.
                    $address = ! empty($individual['profile']['contact']['address']) ? str_replace('<br/>', "\n", $individual['profile']['contact']['address']) : null;

                    $profile_model = new Profile([
                        'description' => $individual['profile']['description'],
                        'website' => ! empty($individual['profile']['contact']['websites']) ? $individual['profile']['contact']['websites'] : null,
                        'address' => $address,
                        'email' => ! empty($individual['profile']['contact']['email']) ? $individual['profile']['contact']['email'] : null,
                        'phone' => ! empty($individual['profile']['contact']['telephone']) ? $individual['profile']['contact']['telephone'] : null,
                        'fax' => ! empty($individual['profile']['contact']['fax']) ? $individual['profile']['contact']['fax'] : null,
                    ]);

                    $individual_model->profile()->save($profile_model);
                }
            }

            if ($directory_model->type === 'full') {
                /* Firms */
                $this->info('Importing ' . $directory_model->name . ' firms...');
                foreach ($directory_api->firms() as $firm) {
                    // Make comma-separated into an array.
                    $other_offices = ! empty($firm['profile']['otherOffices']) ? explode(', ', strip_tags($firm['profile']['otherOffices'])) : null;

                    // Make an HTML list into an array.
                    $clients = ! empty($firm['profile']['sampleClients']) ? array_map('strip_tags', explode('</li><li>', $firm['profile']['sampleClients'])) : null;

                    $firm_model = Firm::updateOrCreate([
                        'uuid' => $firm['id'],
                        'directory_id' => $directory_model->id,
                    ], [
                        'name' => str_replace('&apos;', '\'', $firm['name']),
                        'other_offices' => $other_offices,
                        'clients' => $clients,
                    ]);

                    if ($firm['profile'] && ! empty($firm['profile']['description'])) {
                        // Switch <br/> to line breaks.
                        $address = ! empty($firm['profile']['contact']['address']) ? str_replace('<br/>', "\n", $firm['profile']['contact']['address']) : null;
                        if ($firm_model->profile) {
                            $firm_model->profile->delete();
                        }
                        $profile_model = new Profile([
                            'description' => $firm['profile']['description'],
                            'website' => ! empty($firm['profile']['contact']['websites']) ? $firm['profile']['contact']['websites'] : null,
                            'address' => $address,
                            'email' => ! empty($firm['profile']['contact']['email']) ? $firm['profile']['contact']['email'] : null,
                            'phone' => ! empty($firm['profile']['contact']['telephone']) ? $firm['profile']['contact']['telephone'] : null,
                            'fax' => ! empty($firm['profile']['contact']['fax']) ? $firm['profile']['contact']['fax'] : null,
                        ]);

                        $firm_model->profile()->save($profile_model);
                        $firm_model->contacts()->delete();
                        if ($firm['profile']['professionalContacts']) {
                            foreach ($firm['profile']['professionalContacts'] as $cardinality => $contact) {
                                if ($contact['email']) {
                                    $contact_model = new Contact([
                                        'name' => $contact['name'],
                                        'position' => ! empty($contact['title']) ? $contact['title'] : null,
                                        'email' => ! empty($contact['email']) ? $contact['email'] : null,
                                    ]);
                                    $firm_model->contacts()->save($contact_model);
                                }
                            }
                        }
                        $firm_model->recommendations()->detach();
                        if ($firm['profile']['recommended']) {
                            foreach ($firm['profile']['recommended'] as $recommended) {
                                try {
                                    $individual_model = $directory_model->individuals()->where('uuid', $recommended)->firstOrFail();
                                } catch (ModelNotFoundException $exception) {
                                    $this->error('Could not find individual for recommendation: ' . $recommended);
                                    continue;
                                }

                                $firm_model->recommendations()->attach($individual_model);
                            }
                        }
                    }

                    // Logo
                    if (parse_url($firm['firmLogo'], PHP_URL_PATH)) { // Rather than say there is no logo, the path given is the brand's domain.
                        try {
                            $firm_model->addMediaFromUrl($firm['firmLogo'])->toMediaCollection('logo');
                        } catch (UnreachableUrl $exception) {
                            $this->output->error($exception->getMessage());
                        }
                    }
                }

                /* Jurisdictions */
                $this->info('Importing ' . $directory_model->name . ' jurisdictions...');
                $jurisdictions = collect($directory_api->jurisdictions())->map(function ($jurisdiction) use ($directory_model) {
                    $jurisdiction_model = Jurisdiction::updateOrCreate([

                        'uuid' => $jurisdiction['id'],
                        'directory_id' => $directory_model->id,
                    ], [
                        'region' => $jurisdiction['region'],
                        'name' => $jurisdiction['name'],
                    ]);

                    return $jurisdiction_model;
                });

                /* Rankings */
                $this->info('Importing ' . $directory_model->name . ' rankings...');

                foreach ($jurisdictions as $jurisdiction_model) {
                    try {
                        $rankings = $directory_api->rankings($jurisdiction_model->uuid);
                        $this->info('Starting ' . $jurisdiction_model->name);
                    } catch (RequestException $exception) {
                        $this->error('HTTP error while trying to fetch rankings for "' . $jurisdiction_model->name . '"');
                        continue;
                    }

                    $description = ! empty($rankings['intro']) ? $rankings['intro'] : null;
                    if (strtolower(substr($description, 0, 3)) !== '<p>') {
                        $description = '<p>' . $description . '</p>';
                    }

                    $otherExperts = ! empty($rankings['otherRecommendedExperts']) ? $rankings['otherRecommendedExperts'] : null;

                    if ($otherExperts) {
                        $this->rewriteLinks($directory_model, $otherExperts);
                    }

                    $jurisdiction_model->update([
                        'description' => $description,
                        'barrister_experts' => ! empty($rankings['barristerOtherRecommendedExperts']) ? $rankings['barristerOtherRecommendedExperts'] : null,
                        'other_experts' => $otherExperts,
                    ]);
                    $jurisdiction_model->firms()->delete();
                    $jurisdiction_model->editorials()->delete();
                    if ($rankings['rankings']['firm']) {
                        foreach ($rankings['rankings']['firm'] as $group) {
                            // Editorials
                            if (isset($group['editorial'])) {
                                foreach ($group['editorial'] as $editorial) {
                                    try {
                                        $firm_model = $directory_model->firms()->where('uuid', $editorial['id'])->firstOrFail();
                                    } catch (ModelNotFoundException $exception) {
                                        $this->error('Could not find firm for editorials: "' . $editorial['name'] . '" (' . $editorial['id'] . ')');
                                        continue;
                                    }

                                    // The source DB has duplicates, for some reason.
                                    $already_has_editorial = $jurisdiction_model->editorials()->where('directory_firm_id', $firm_model->id)->exists();
                                    if ($already_has_editorial) {
                                        continue;
                                    }

                                    $description = ! empty($editorial['description']) ? $editorial['description'] : null;
                                    if (strtolower(substr($description, 0, 3)) !== '<p>') {
                                        $description = '<p>' . $description . '</p>';
                                    }

                                    $editorial_model = new Editorial([
                                        'type' => strtolower($group['type']),
                                        'name' => $editorial['name'],
                                        'description' => $description,
                                    ]);

                                    $editorial_model->firm()->associate($firm_model);
                                    $editorial_model->jurisdiction()->associate($jurisdiction_model);
                                    $editorial_model->save();
                                }
                            }

                            // Rankings
                            foreach ($group['tables'] as $table) {
                                foreach ($table['ranks'] as $rank) {
                                    foreach ($rank['ranked'] as $ranked) {
                                        try {
                                            $firm_model = $directory_model->firms()->where('uuid', $ranked['id'])->firstOrFail();
                                        } catch (ModelNotFoundException $exception) {
                                            $this->error('Could not find firm for rankings: "' . $ranked['name'] . '" (' . $ranked['id'] . ')');
                                            continue;
                                        }

                                        $ranking_model = new Ranking([
                                            'name' => $ranked['name'],
                                            'type' => strtolower($group['type']),
                                            'group_order' => $table['order'],
                                            'group_name' => $table['name'],
                                            'rank' => $rank['rank'],
                                        ]);

                                        $ranking_model->rankable()->associate($firm_model);
                                        $ranking_model->jurisdiction()->associate($jurisdiction_model);
                                        $ranking_model->save();
                                    }
                                }
                            }
                        }
                    }

                    // Individuals
                    $jurisdiction_model->individuals()->delete();
                    if ($rankings['rankings']['individual']) {
                        foreach ($rankings['rankings']['individual'] as $group) {
                            foreach ($group['tables'] as $table) {
                                foreach ($table['ranks'] as $rank) {
                                    foreach ($rank['ranked'] as $ranked) {
                                        try {
                                            $individual_model = $directory_model->individuals()->where('uuid', $ranked['id'])->firstOrFail();
                                        } catch (ModelNotFoundException $exception) {
                                            $this->error('Could not find "' . $ranked['name'] . '" (' . $ranked['id'] . ')');
                                            continue;
                                        }

                                        $ranking_model = new Ranking([
                                            'name' => $ranked['name'],
                                            'subname' => $ranked['firmName'],
                                            'type' => strtolower($group['type']),
                                            'group_order' => $table['order'],
                                            'group_name' => $table['name'],
                                            'rank' => $rank['rank'],
                                        ]);

                                        $ranking_model->rankable()->associate($individual_model);
                                        $ranking_model->jurisdiction()->associate($jurisdiction_model);
                                        $ranking_model->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /* Rewrite links */
        $this->info('Rewriting links...');

        foreach ($directories as $directory_model) {
            foreach ($directory_model->editorials()->cursor() as $editorial_model) {
                $description = $editorial_model->description;

                if ($this->rewriteLinks($directory_model, $description)) {
                    $editorial_model->description = $description;
                    $editorial_model->save();
                }
            }
        }
        $this->info('Calling directories routes');
        $this->call('directories:routes', []);
        $this->info('Calling search index rebuild to fix profiles');

        $this->info('Done in ' . (microtime(true) - $t));
    }

    private function rewriteLinks(Directory $directory, string &$text): bool
    {
        $domDocument = new DOMDocument();
        // wrap with <div>
        $domDocument->loadHTML('<?xml encoding="utf-8" ?><div>' . $text . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $rewritten = false;

        foreach ($domDocument->getElementsByTagName('a') as $link) {
            if ($uuid = $link->getAttribute('data-profileid')) {
                if ($individual = $directory->individuals()->where('uuid', $uuid)->first()) {
                    $route = '/directories/' . $directory->getRouteKey() . '/individuals/' . $individual->getRouteKey() . '';

                    $link->setAttribute('href', $route);
                    $link->removeAttribute('data-profileid');

                    $rewritten = true;

                    $dom = new DOMDocument();
                    $dom->loadHTML($domDocument->saveHtml());
                    $divs = $dom->getElementsByTagName('div');
                    // remove <div> wrap
                    $text = $dom->saveHTML($divs->item(0));
                } else {
                    $this->info('Could not rewrite link for "' . $link->nodeValue . '" (' . $uuid . ')');
                }
            }
        }

        return $rewritten;
    }

    private function getDirectoriesToImport()
    {
        $directories = Directory::get();
        $directoriesToImport = $this->option('directories');
        if (! $directoriesToImport) {
            return $directories;
        }

        foreach (explode(',', $directoriesToImport) as $directorySlug) {
            $directories = $directories->filter(function ($directory) use ($directorySlug) {
                return $directory->slug === $directorySlug;
            });
        }

        if ($directories->isEmpty()) {
            $this->error('Invalid directories were specified');
            die();
        }

        return $directories;
    }

    /**
     * @param $directories
     * @throws \Exception
     */
    private function deletePreviousData($directories)
    {
        if (! $this->option('delete-previous-data')) {
            return;
        }
        try {
            \DB::beginTransaction();
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($directories as $directory) {
                /* @var \App\Models\Directory $directory */
                $this->info("Deleting previous data from {$directory->name}");
                $this->info('---------------------');
                $count = $directory->individuals()->whereHas('profile')->delete();
                $this->info("{$count} from directory_profiles has been deleted");
                $count = $directory->individuals()->forceDelete();
                $this->info("{$count} from directory_individuals has been deleted");
                $count = $directory->firms()->whereHas('contacts')->forceDelete();
                $this->info("{$count} from directory_contacts has been deleted");
                $count = $directory->firms()->whereHas('recommendations')->forceDelete();
                $this->info("{$count} from directory_recommended_individuals has been deleted");
                $count = $directory->firms()->forceDelete();
                $this->info("{$count} from directory_firms has been deleted");
                $count = $directory->editorials()->forceDelete();
                $this->info("{$count} from directory_editorials has been deleted");
                $jurisdictionIds = $directory->jurisdictions()->pluck('id')->toArray();
                $count = Ranking::whereIn('directory_jurisdiction_id', $jurisdictionIds)->forceDelete();
                $this->info("{$count} from directory_rankings has been deleted");
                $count = $directory->jurisdictions()->forceDelete();
                $this->info("{$count} from directory_jurisdictions has been deleted");
            }

            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}
