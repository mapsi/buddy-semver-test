<?php

namespace App\Services\ContentApi\Entities;

class IndustryJurisdiction extends DataEntity
{
    const ENTITY_TYPE_TAG_ID = '';

    public function getFirms()
    {
        return $this->getRelations(Firm::class);
    }
}
