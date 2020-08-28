<?php

namespace App\Models\Drupal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class SyncError extends Model
{
    protected $table = 'drupal_sync_errors';

    protected $guarded = [];

    /**
     * Make sure we ignore updated at
     */
    public function setUpdatedAtAttribute()
    {
        return;
    }

    /**
     *
     */
    public static function log($entity, \Exception $exception)
    {
        $uuid = Arr::get($entity, 'id', 'N.A.');
        $entityId = Arr::get($entity, 'attributes.nid', 'N.A.');
        $entityType = Arr::get($entity, 'type', 'N.A.');

        static::create([
            'drupal_object_uuid' => $uuid,
            'drupal_entity_type' => $entityType,
            'drupal_entity_id' => $entityId,
            'exception_origin' => $exception->getFile() . ':' . $exception->getLine(),
            'message' => $exception->getMessage(),
        ]);
    }
}
