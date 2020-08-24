<?php

namespace App\Console\Commands\Drupal;

use App\Models\Brand;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * USAGE
 * This is used to import GDR initial content
 *
 * @package App\Console\Commands\Drupal
 */
class ImportInitialGdrContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:initial-gdr-content
        {--model= : Imports a specific model}
        {--all : Imports all the models}
        {--since= : Y-m-d - Sets the date from where we should pick changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets and syncs initial GDR content';

    /**
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '900M');
        $this->info('Started a content update run.');

        if ($since = $this->option('since')) {
            $since = DateTime::createFromFormat('Y-m-d', $since);
        }

        if ($this->option('model')) {
            $model = Str::contains($this->option('model'), 'App\\')
                ? $this->option('model')
                : "App\\Models\\{$this->option('model')}";

            $this->synchronise([$model], $since, in_array($model, $this->getGdrContentToImport()));

            return;
        }

        if (! $this->option('all')) {
            $this->error('No entities specified');

            return;
        }

        $this->synchronise($this->getModelsToImport(), $since, false);
        $this->synchronise($this->getGdrContentToImport(), $since, true);

        $this->info('Finished.');
    }

    protected function synchronise(array $models, DateTime $since, bool $withApiIdDefined = false)
    {
        foreach ($models as $model) {
            $modelName = Str::plural(str_replace('_', ' ', Str::snake(class_basename($model)))); // Makes it human readable.

            $this->info('Updating ' . $modelName);

            $model::importFromDrupal($this->output, $since, $withApiIdDefined);
        }
    }

    /**
     * @return array
     */
    protected function getModelsToImport()
    {
        return [
            Brand::class,
        ];
    }
}
