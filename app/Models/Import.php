<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;

/**
 * The Import model maps an entity's UUID to the model in Laravel. It also tracks when that entity
 * was last imported.
 *
 * @property string uuid
 * @method static WhereUuidIs(string $uuid)
 */
class Import extends Model
{
    protected $fillable = [
        'uuid',
        'entity_type',
        'entity_id',
        'reimport',
        'brand_machine_code',
        'importable_id',
        'importable_type',
    ];

    public function importable()
    {
        return $this->morphTo();
    }

    /* Statics */

    public static function register($model, array $entity, DateTime $timestamp = null, $brandMachineCode = null)
    {
        $entity_type_id_fields = [
            'taxonomy_term' => 'tid',
            'node' => 'nid',
            'redirect' => 'rid',
        ];

        if (! $entity_type = substr($entity['type'], 0, strpos($entity['type'], '--'))) {
            throw new Exception('Could not find entity type.');
        }

        if (! array_key_exists($entity_type, $entity_type_id_fields)) {
            throw new Exception('Could not find key for entity type.');
        }

        /**
         * @todo hotfix
         */
        $importToUse = null;

        $imports = self::where([
            'uuid' => $entity['id'],
            'entity_type' => $entity_type,
            'entity_id' => $entity['attributes'][$entity_type_id_fields[$entity_type]],
        ])
            ->get();

        if ($imports->count() > 0) {
            /** Iterate over existing imports and try to match on `brand_machine_code` */
            foreach ($imports as $import) {
                if (! is_null($import->brand_machine_code) && $import->brand_machine_code == $brandMachineCode) {
                    $importToUse = $import;
                    break;
                }
            }

            if (! $importToUse) {
                /**
                 * Couldn't find by `machine_brand_code`, so iterate again and reach out to the brand of the importable,
                 * and compare that brand to the one we are looking for
                 */
                foreach ($imports as $import) {
                    if (isset($import->importable->brand->machine_name) && $import->importable->brand->machine_name == $brandMachineCode) {
                        $importToUse = $import;
                        /** Since we matched by importable, update `brand_machine_code` on import */
                        $importToUse->brand_machine_code = $brandMachineCode;
                        break;
                    }
                }
            }

            if (! $importToUse) {
                /** Fall back to using existing non-branded import entity */
                foreach ($imports as $import) {
                    if (is_null($import->brand_machine_code)) {
                        $importToUse = $import;
                        /** Since we matched by fallback, update `brand_machine_code` on import */
                        $importToUse->brand_machine_code = $brandMachineCode;
                        break;
                    }
                }
            }
        }

        if (! $importToUse) {
            /**
             * No entries for UUID/type/id; create new. Logic from previous commit, leaving as-is.
             */
            $importToUse = self::firstOrNew(
                [
                    'uuid' => $entity['id'],
                    'entity_type' => $entity_type,
                    'entity_id' => $entity['attributes'][$entity_type_id_fields[$entity_type]],
                    'brand_machine_code' => $brandMachineCode,
                ]
            );
        }

        $import = $importToUse;
        /**
         * @todo hotfix end
         */

        $import->reimport = 0;
        $import->updated_at = $timestamp ?? new DateTime();
        $import->importable()->associate($model);
        $import->save();

        return $import;
    }

    /**
     * Gets the last updated entity by optional model class.
     *
     * @return Carbon|null
     */
    public static function getLastUpdatedAt(string $class = null)
    {
        $query = static::orderBy('updated_at', 'desc');

        if ($class) {
            $query->where('importable_type', $class);
        }

        if ($result = $query->first()) {
            return $result->updated_at;
        }

        return null;
    }

    public static function firstByUuid($uuid)
    {
        try {
            return static::firstByUuidOrFail($uuid);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    public static function firstByUuidOrFail($uuid)
    {
        return static::query()->whereUuidIs($uuid)->firstOrFail();
    }

    /* Scopes */

    public function scopeWhereUuidIs($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    public function scopeWhereUuidIn($query, array $uuids)
    {
        return $query->whereIn('uuid', $uuids);
    }

    public function scopeWhereEntityIs($query, string $entity_type, $entity_id)
    {
        return $query->where('entity_type', $entity_type)->where('entity_id', $entity_id);
    }

    public function scopeWithUnpublished($query)
    {
        return $query->with(['importable' => function ($query) {
            $query->withoutGlobalScope('is_published');
        }]);
    }

    public function scopeBrandCode($query, $brandCode)
    {
        return $query->where('brand_machine_code', $brandCode);
    }

    /**
     * A convenience method that reduces code duplication. This work was previously embedded in
     * classes, ad infinitum.
     *
     * @param array  $entityData
     * @param string $foreignPropName
     * @param string model
     * @return int
     * @throws ModelNotFoundException
     * @throws \RuntimeException if the requested property is missing in the given $entityData
     */
    public static function extractAttributeAndReturnEntityId(
        array $entityData,
        string $foreignPropName,
        string $model = null
    ) {
        $dataPointer = sprintf('relationships.%s.data', $foreignPropName);
        $data = Arr::get($entityData, $dataPointer);
        if (is_null($data)) {
            // Relationship does not exist in JSON response
            return;
        }

        $uuid = Arr::get($data, 'id');
        if ($uuid == 'virtual') {
            return;
        }

        if (is_null($uuid)) {
            throw new \RuntimeException("No data found for given $dataPointer");
        }

        try {
            $entity = self::firstByUuidOrFail($uuid)->importable;
        } catch (ModelNotFoundException $exception) {
            throw new ModelNotFoundException("Import entry for model '$model' not found with UUID '$uuid'");
        }

        return $entity->id;
    }

    /**
     * @param array  $drupalData
     * @param string $relationshipFieldName
     * @param string $mediaFieldName
     * @param string $collectionName
     * @param Model  $model
     * @return void
     */
    public static function importMediaFromDrupalAndAddToMediaCollection(
        array $drupalData,
        string $relationshipFieldName,
        string $mediaFieldName,
        string $collectionName,
        $model
    ) {
        $mediaFiles = self::parseMediaData($drupalData, $relationshipFieldName);

        foreach ($mediaFiles as $mediaFileData) {
            $fileMediaUrlPointer = "relationships.$mediaFieldName.data.attributes.url";
            $mediaUrl = Arr::get($mediaFileData, $fileMediaUrlPointer);

            if (! $mediaUrl) {
                Log::warning("Media under '$fileMediaUrlPointer' not found for entity " .
                    class_basename(__CLASS__) . " with ID: {$drupalData['id']}");
                continue;
            }

            self::importMediaToModel($mediaUrl, $collectionName, $model);
        }
    }

    /**
     * @param string $mediaUrl
     * @param string $collectionName
     * @param Model  $model
     */
    private static function importMediaToModel($mediaUrl, $collectionName, $model)
    {
        try {
            $model->addMediaFromUrl($mediaUrl)
                ->usingName(basename($mediaUrl))
                ->toMediaCollection($collectionName);
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
        }
    }

    /**
     * The value of the media relationship from the Drupal response must be parsed since it returns
     * an array or an object depending on whether the entity contains multiple media files or not.
     * This will return always an iterable array to fetch all media whether to be multiple or not.
     *
     * @param $drupalData
     * @param $relationshipFieldName
     * @return array
     */
    private static function parseMediaData($drupalData, $relationshipFieldName)
    {
        $key = "relationships.{$relationshipFieldName}.data";
        $mediaData = Arr::get($drupalData, $key) ?: [];

        if (! self::containsMultipleMediaFiles($mediaData)) {
            return [$mediaData];
        }

        return $mediaData;
    }

    /**
     * @param $mediaData
     * @return bool
     */
    private static function containsMultipleMediaFiles($mediaData)
    {
        return ! key_exists('relationships', $mediaData);
    }
}
