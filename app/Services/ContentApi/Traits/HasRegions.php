<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\TagGroups\Country;

trait HasRegions
{
    public function getRegions()
    {
        return $this->getTagGroup(Country::class);
    }
}
