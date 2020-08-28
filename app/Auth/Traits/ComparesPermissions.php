<?php

declare(strict_types=1);

namespace App\Auth\Traits;

use App\Models\Brand;
use App\Models\User;

trait ComparesPermissions
{
    private function comparePermissions(User $user, Brand $brand = null): bool
    {
        $requiredPermissions = $this->requiresPermissions();
        if (count($requiredPermissions) > 0) {
            if (is_null($brand)) {
                $brand = resolve(Brand::class);
            }
            return $user->hasAccessToFeatures($requiredPermissions, $brand);
        }

        return false;
    }
}
