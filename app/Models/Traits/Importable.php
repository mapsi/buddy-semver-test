<?php

namespace App\Models\Traits;

use App\Exceptions\ImportAbortedException;
use App\Jobs\ClearCache;
use App\Models\Drupal\SyncError;
use App\Models\Import;
use App\Models\Interfaces\Brandable;
use App\Models\Interfaces\HasContentSections;
use App\Models\Interfaces\Routable;
use DateTime;
use Facades\App\Classes\Drupal;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait Importable
{
    abstract protected static function getEntityBundle();

    /**
     * @return string
     */
    protected static function getEntityType(): string
    {
        return 'taxonomy_term';
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     * @return mixed
     */
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $this->fill([
            'name' => $entity['attributes']['name'],
        ]);

        return $this->save();
    }

    /**
     * Include a set of fields added to the Drupal query. Therefore they will be populated in the
     * response with extra information needed.
     *
     * @return array
     */
    protected static function getEntityFields(): array
    {
        return [];
    }

    /**
     * @param DateTime|null $since_date
     * @return array
     */
    public static function getFilters(DateTime $since_date = null): array
    {
        $filters = [];

        if (! $since_date) {
            $since_date = Import::getLastUpdatedAt(static::class);
        }

        if ($since_date) {
            $filters[] = [
                'field' => 'changed',
                'value' => $since_date->getTimestamp(),
                'operator' => '>',
            ];
        }

        return $filters;
    }

    /**
     * Delete brands that are no longer being passed (un-checked in Drupal)
     *
     * @param $entity
     * @param $drupalEntityBrands
     * @return mixed
     */
    protected static function deleteBrandsNotPresentInEntity($entity, $drupalEntityBrands)
    {
        $existingImports = Import::whereUuidIs($entity['id'])->with(['importable' => function ($query) {
            $query->withoutGlobalScope('is_published');
        }])->get();

        foreach ($existingImports as $existingImport) {
            /** Is it being passed now? */
            $stillExists = false;
            foreach ($drupalEntityBrands as $drupalEntityBrand) {
                $brand = Import::whereUuidIs($drupalEntityBrand['id'])->firstOrFail()->importable;

                if ($brand->machine_name === $existingImport->brand_machine_code) {
                    $stillExists = true;
                    break;
                }
            }

            if (! $stillExists && $existingImport->importable) {
                $existingImport->importable->delete();
                $existingImport->delete();
            }
        }
    }

    /**
     * Brandable: process all
     *
     * @param OutputStyle        $output
     * @param                    $fieldBrands
     * @param EloquentCollection $searchUpdate
     * @param                    $entity
     * @return mixed
     * @throws ImportAbortedException
     */
    protected static function importBrandableEntity(OutputStyle $output, $fieldBrands, EloquentCollection $searchUpdate, $entity)
    {
        foreach ($fieldBrands as $fieldBrand) {
            $searchUpdate = self::importSingular($searchUpdate, [], $entity, $output, $fieldBrand);
        }

        self::deleteBrandsNotPresentInEntity($entity, $fieldBrands);

        return $searchUpdate;
    }

    /**
     * @return mixed
     */
    public function import()
    {
        return $this->morphOne(Import::class, 'importable');
    }

    /**
     * @param array $uuids
     * @return \Illuminate\Database\Eloquent\Builder[]|EloquentCollection
     */
    public static function findUuidsOrFail(array $uuids)
    {
        $importables = static::query()
            ->whereHas(
                'import',
                function ($query) use ($uuids) {
                    $query->whereIn('uuid', $uuids);
                }
            )
            ->withoutGlobalScope('is_published')
            ->get();

        if ($importables->count() !== count($uuids)) {
            throw new ModelNotFoundException();
        }

        return $importables;
    }

    /**
     * @param OutputStyle   $output
     * @param DateTime|null $since_date
     * @throws ImportAbortedException
     */
    public static function importFromDrupal(OutputStyle $output, DateTime $since_date = null, bool $apiIdMustBeDefined = false)
    {
        $entities = Drupal::getEntities(static::getEntityType(), static::getEntityBundle())
            ->withFields(static::getEntityFields());

        foreach (static::getFilters($since_date) as $filter) {
            $entities->addFilter($filter['field'], $filter['value'], $filter['operator'] ?? null);
        }

        if ($apiIdMustBeDefined) {
            $entities->addCustomFilter('api_id', 'field_api_id', null, 'IS NOT NULL');
        }

        self::importFromDrupalList($output, null, $entities);
    }

    /**
     * @return string|null
     */
    public static function getEntityId()
    {
        switch (static::getEntityType()) {
            case 'node':
                return 'nid';
                break;
            case 'taxonomy_term':
                return 'tid';
                break;
        }

        return null;
    }

    /**
     * @param OutputStyle $output
     * @param             $progress_bar
     * @param             $nids
     * @return void
     * @throws ImportAbortedException
     */
    public static function importEntityFromDrupal(OutputStyle $output, $progress_bar, $nids)
    {
        $fields = static::getEntityFields();
        $entities = Drupal::getEntities(static::getEntityType(), static::getEntityBundle())->withFields($fields);

        if (static::getEntityId()) {
            $filters = [
                [
                    'field' => static::getEntityId(),
                    'value' => $nids,
                    'operator' => 'IN',
                ],
            ];

            foreach ($filters as $filter) {
                $entities->addFilter($filter['field'], $filter['value'], $filter['operator'] ?? null);
            }

            $entitiesChecked = [];

            foreach ($entities as $entity) {
                $attributes = $entity['attributes'][static::getEntityId()];

                if (array_search($attributes, $nids) === false) {
                    $exStr = sprintf(
                        'Unexpected entity %s expecting %s',
                        print_r($attributes, true),
                        print_r($nids, true)
                    );

                    throw new \Exception($exStr);
                }

                $entitiesChecked[] = $entity;

                if (count($entitiesChecked) > 10) {
                    self::importFromDrupalList($output, $progress_bar, $entitiesChecked);

                    $entitiesChecked = [];
                }
            }

            if (! empty($entitiesChecked)) {
                self::importFromDrupalList($output, $progress_bar, $entitiesChecked);
            }
        }
    }

    /**
     * Method created to avoid code duplication during refactoring.
     *
     * @return array
     */
    public static function clearSearchUpdateStackAndCache($searchUpdate, $relations): array
    {
        if ($searchUpdate->count() >= 500) {
            $searchUpdate->load(array_keys($relations))->searchable();
            $relations = [];
            $searchUpdate = new EloquentCollection();

            self::clearCache();
        }

        return [
            $relations,
            $searchUpdate,
        ];
    }

    /**
     * Method created to avoid code duplication during refactoring.
     *
     * @return bool
     */
    public static function clearCache(): bool
    {
        $delay = now()->addSeconds(10);

        ClearCache::dispatch()->delay($delay);

        return true;
    }

    /**
     * Method created to avoid code duplication during refactoring.
     *
     * @return void
     * @throws ImportAbortedException
     */
    public static function checkForStopSignal()
    {
        if (Cache::pull('stop_content_import')) {
            logger('Stopped import.');

            throw new ImportAbortedException();
        }
    }

    /**
     * Method updated to avoid code duplication during refactoring.
     *
     * @param OutputStyle $output
     * @param             $entities
     * @return void
     * @throws ImportAbortedException
     */
    public static function importFromDrupalList(OutputStyle $output, $progressBar, $entities)
    {
        $searchUpdate = new EloquentCollection();
        foreach ($entities as $entity) {
            $fieldBrands = self::extractBrandsFromEntity($entity);

            if ($fieldBrands && app(__CLASS__) instanceof Brandable) {
                $searchUpdate = self::importBrandableEntity($output, $fieldBrands, $searchUpdate, $entity);
            } else {
                /** Non-brandable; process singularly */
                $searchUpdate = self::importSingular($searchUpdate, [], $entity, $output);
            }
        }

        if ($searchUpdate->count()) {
            $searchUpdate->searchable();
            self::clearCache();
        }
    }

    /**
     * @param array $entity
     * @return mixed
     */
    private static function extractBrandsFromEntity(array $entity)
    {
        $brands = Arr::get($entity, 'relationships.field_brands.data') ??
            Arr::get($entity, 'relationships.field_brand.data');

        return array_has($brands, 'id') ? [$brands] : $brands;
    }

    /**
     * Method created to avoid code duplication during refactoring.
     *
     * @param      $searchUpdate
     * @param      $relations
     * @param      $entity
     * @param      $output
     * @param null $fieldBrand
     * @return mixed
     * @throws ImportAbortedException
     */
    public static function importSingular($searchUpdate, $relations, $entity, $output, $fieldBrand = null)
    {
        $brand = null;

        if ($fieldBrand) {
            $brandId = Arr::get($fieldBrand, 'id');
            $brand = optional(Import::whereUuidIs($brandId)->first())->importable;

            if (! $brand) {
                throw new \Exception("Brand {$brandId} does not exist");
            }
        }

        list($relations, $searchUpdate) = self::clearSearchUpdateStackAndCache($searchUpdate, $relations);

        self::checkForStopSignal();

        try {
            $model = \DB::transaction(function () use ($entity, $searchUpdate, $relations, $output, $brand) {

                $model = self::getImportForEntity($entity, $brand);

                if (! $model) {
                    throw new \Exception('Importable not found for: ' . $entity['id'] ?? 'N.A.');
                }

                if ($brand && $model instanceof Brandable) {
                    $model->brand()->associate($brand);
                }

                $model->updateFromDrupal($entity, $output);

                self::recordSuccessfulImportModel($model, $entity, $brand->machine_name ?? null);

                // @todo this needs to be analysed for legacy data
                // @todo needs to be optimised since it deletes and inserts every time
                if ($model instanceof HasContentSections && $brand) {
                    $model->attachContentSections($entity, $output);
                }

                if ($model instanceof Routable) {
                    self::addPath($entity, $model, $brand);
                }

                if ($model instanceof Searchable) {
                    $searchUpdate->push($model);
                    $model->setRelations([]);
                }

                return $model;
            });

            self::outputSuccess($output, $model ?? null);
        } catch (\Exception $exception) {
            self::outputError($output, $model ?? '', $entity, $exception);
            SyncError::log($entity, $exception);
        }

        return $searchUpdate;
    }

    /**
     * $beforeMultipleBrandsImplementationImport
     * This causes the brand_machine_name to be NULL
     *
     * @param array      $entity
     * @param Brand|null $brand
     * @return mixed
     */
    public static function getImportForEntity($entity, $brand = null)
    {
        $import = Import::whereUuidIs($entity['id'])->with(['importable' => function ($query) {
            $query->withoutGlobalScope('is_published');
        }]);

        if ($brand) {
            $import->brandCode($brand->machine_name);
        }

        if ($firstImport = $import->first()) {
            return $firstImport->importable ?: new static();
        }

        $beforeMultipleBrandsImplementationImport = Import::whereUuidIs($entity['id'])->with(['importable' => function ($query) {
            $query->withoutGlobalScope('is_published');
        }])->whereNull('brand_machine_code')->first();

        return $beforeMultipleBrandsImplementationImport
            ? $beforeMultipleBrandsImplementationImport->importable ?: new static()
            : new static();
    }

    /**
     * @param $model
     * @param $entity
     * @param $machineName
     * @return Import
     * @throws \Exception
     */
    public static function recordSuccessfulImportModel($model, $entity, $machineName)
    {
        $timestamp = $entity['attributes']['changed'] ?? $entity['attributes']['created'];
        $timestamp = (new DateTime())->setTimestamp($timestamp);

        return Import::register($model, $entity, $timestamp, $machineName);
    }

    /**
     * Method created to avoid code duplication during refactoring.
     *
     * @param array $entity
     * @param       $model
     * @param null  $brand
     * @return bool
     */
    public static function addPath(array $entity, $model, $brand = null): bool
    {
        $path = $entity['attributes']['path']['alias'] ?? null;

        if (! $path) {
            return false;
        }

        $splitPath = explode('/', $path);

        if (count($splitPath) === 0) {
            return false;
        }

        if ($brand) {
            $splitPath[1] = $brand->machine_name;
        }

        $path = implode('/', $splitPath);

        $model->addRoute($path, true);

        return true;
    }

    /**
     * @param array  $field_data
     * @param string $model_class
     * @param bool   $output
     * @param bool   $with_weight
     * @param array  $pivot
     * @param bool   $detach
     * @param bool   $relationship
     * @return void
     */
    protected function syncImportField(
        array $field_data,
        string $model_class,
        $output = false,
        $with_weight = false,
        array $pivot = [],
        $detach = true,
        $relationship = false
    ) {
        try {
            $uuids = array_map(function ($item) {
                return $item['id'];
            }, $field_data);

            $uuids = array_unique($uuids);

            $models = $model_class::findUuidsOrFail(
                collect($uuids)
                    ->filter(function ($item) {
                        return $item != 'virtual';
                    })
                    ->toArray()
            );

            $models->load('import');

            if (! $relationship) {
                $relationship = Str::plural(
                    camel_case(
                        class_basename($model_class)
                    )
                );
            }

            if (method_exists($this, $relationship . 'All')) {
                $relationship .= 'All';
            }

            $syncfn = 'sync';

            if (! $detach) {
                $syncfn = 'syncWithoutDetaching';
            }

            if (! $with_weight) {
                $sync_data = [];

                foreach ($models as $model) {
                    $sync_data[$model->id] = $pivot;
                }

                $this->{$relationship}()->$syncfn($sync_data);
            } else {
                $weights = array_flip($uuids);
                $sync_data = [];

                foreach ($models as $model) {
                    if (isset($weights[$model->import->uuid])) {
                        $sync_data[$model->id] = $pivot + [
                                'weight' => $weights[$model->import->uuid],
                            ];
                    } else {
                        $sync_data = $models;
                    }
                }

                $this->{$relationship}()->$syncfn($sync_data);
            }
        } catch (\Exception $exception) {
            if ($output) {
                $logVars[] = $exception->getTraceAsString();

                $output->error($exception->getMessage());
                $output->error('Model id ' . $this->id . ' ' . $model_class);

                if ($uuids) {
                    $logVars[] = sprintf(
                        'Model id %s %s %s',
                        $this->id,
                        $model_class,
                        print_r($uuids, true)
                    );

                    $output->error(print_r($uuids, true));
                }

                \Log::error(
                    $exception->getMessage(),
                    $logVars
                );
            }
        }
    }

    /**
     * @param OutputStyle $output
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    protected static function getProgressBar(OutputStyle $output)
    {
        $progress_bar = $output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');

        return $progress_bar;
    }

    /**
     * @param $output
     * @param $model
     * @return void
     */
    protected static function outputSuccess($output, $model)
    {
        $action = optional($model)->wasRecentlyCreated ? 'created' : 'updated';
        $modelName = class_basename($model) ?? 'N.A.';

        $output->success($modelName . " $action | ID: {$model->id} | Import Entity UUID: {$model->import->uuid}");
    }

    /**
     * @param $output
     * @param $model
     * @param $entity
     * @param $exception
     */
    protected static function outputError($output, $model, $entity, $exception)
    {
        $errorMessage = class_basename(self::class) . ' failed';
        if ($model->id ?? false) {
            $errorMessage .= " | ID: {$model->id}";
        }
        $errorMessage .= " | Import Entity UUID {$entity['id']}";

        $output->error($errorMessage);
        $output->error($exception->getMessage());

        \Log::error($exception->getMessage(), [$entity['id'], json_encode($entity)]);
    }
}
