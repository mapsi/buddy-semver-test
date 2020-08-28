<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Interfaces\Searchable;
use App\Services\ContentApi\Traits\HasRegions;

abstract class Profile extends DataEntity implements Searchable
{
    use HasRegions;

    const ENTITY_TYPE_TAG_ID = 17591;

    public function __get($name)
    {
        if ($name === 'resource') {
            return $this;
        }
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function getHeadline()
    {
        return $this->data['body'];
    }

    public function getView()
    {
        return null;
    }
}
