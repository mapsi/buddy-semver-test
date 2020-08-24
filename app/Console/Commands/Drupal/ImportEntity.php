<?php

namespace App\Console\Commands\Drupal;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * USAGE
 * IMPORT SINGLE MODEL ENTITY:
 * import:entities --model=Article --uuid=1b8f6256-7008-43dc-be3c-713f4c85f571 --nid=15
 * IMPORT SINGLE ALL ENTITIES FROM SINGLE MODEL:
 * import:entities --model=Article
 * IMPORT ALL ENTITIES:
 * import:entities --all
 * (See additional options)
 *
 * @package App\Console\Commands\Drupal
 */
class ImportEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:entity
        {--model= : Imports a specific model}
        {--modelId= : Re-import existing model}
        {--uuid= : Entity UUID (Needed when trying to update a single entity from a model)}
        {--nid= : Entity NID (Needed when trying to update a single entity from a model)}
        {--debug= : (true|false) Logs Entity JSON Response from Drupal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads all content';

    /**
     * @return void
     */
    public function handle()
    {
        $this->info('Started a content update run.');

        if (! $this->option('model')) {
            $this->warn('You have to specify a model');

            return;
        }

        if (! $this->option('modelId') && (! $this->option('uuid') || ! $this->option('nid'))) {
            $this->warn('You have to specify a model_id or UUID and NID');

            return;
        }

        $model = Str::contains($this->option('model'), 'App\\')
            ? $this->option('model')
            : "App\\Models\\{$this->option('model')}";

        list($entityUuid, $entityNid) = $this->resolveDrupalEntityIds($model);

        $this->importSingleEntity($model, $entityUuid, $entityNid);

        $this->info('Finished.');
    }

    /**
     * @param string $model
     * @param        $entityUuid
     * @param        $entityNid
     */
    protected function importSingleEntity($model, $entityUuid, $entityNid)
    {
        $model::importEntityFromDrupal($this->output, null, [$entityUuid => $entityNid]);
    }

    /**
     * @param $model
     * @return array
     */
    protected function resolveDrupalEntityIds($model): array
    {
        $entityUuid = $this->option('uuid');
        $entityNid = $this->option('nid');

        if ($modelId = $this->option('modelId')) {
            $import = $model::find($modelId)->import;

            $entityUuid = $import->uuid;
            $entityNid = $import->entity_id;
        }

        return [$entityUuid, $entityNid];
    }
}
