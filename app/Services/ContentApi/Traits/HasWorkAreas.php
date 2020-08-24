<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\TagGroups\WorkArea;
use Illuminate\Support\Collection;

trait HasWorkAreas
{
    public function getWorkAreas(): Collection
    {
        return $this->getTagGroup(WorkArea::class);
    }
}
