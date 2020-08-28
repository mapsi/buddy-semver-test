<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\Entities\Section;

trait HasSections
{
    public function getSection()
    {
        return $this->getRelations(Section::class)->first();
    }
}
