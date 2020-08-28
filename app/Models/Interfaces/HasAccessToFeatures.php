<?php

declare(strict_types=1);

namespace App\Models\Interfaces;

use App\Models\Brand;

interface HasAccessToFeatures
{
    public function getFeatureCacheTags(): array;
    public function getFeatureAccess(Brand $brand): array;
    public function hasAccessToFeatures(array $features, Brand $brand): bool;
}
