<?php

namespace App\Console\Commands\Drupal;

use App\Models\Brand;
use App\Models\Redirect;
use DateTime;
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
class ImportEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:entities
        {--model= : Imports a specific model}
        {--all : Imports all the models}
        {--since= : Y-m-d - Sets the date from where we should pick changes}';

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
        ini_set('memory_limit', '900M');
        $this->info('Started a content update run.');

        if ($this->option('model')) {
            $model = Str::contains($this->option('model'), 'App\\')
                ? $this->option('model')
                : "App\\Models\\{$this->option('model')}";

            $models = [$model];
        }

        if ($this->option('all')) {
            $models = $this->getModelsInDependencyOrder();
        }

        if (empty($models)) {
            $this->error('No entities specified');

            return;
        }

        if ($since = $this->option('since')) {
            $since = DateTime::createFromFormat('Y-m-d', $since);
        }

        foreach ($models as $model) {
            $modelName = Str::plural(str_replace('_', ' ', Str::snake(class_basename($model)))); // Makes it human readable.

            $this->info('Updating ' . $modelName);

            $model::importFromDrupal($this->output, $since);
        }

        $this->info('Finished.');
    }

    /**
     * @return array
     */
    protected function getModelsInDependencyOrder()
    {
        return [
            Redirect::class,
        ];
    }
}
