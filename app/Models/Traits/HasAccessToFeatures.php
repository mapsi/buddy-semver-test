<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Brand;

trait HasAccessToFeatures
{
    public function hasAccessToFeatures(array $requiredPermissions, Brand $brand): bool
    {
        $userFeatures = $this->getFeatureAccess($brand);
        return (bool) array_intersect($requiredPermissions, $userFeatures);
    }
}
