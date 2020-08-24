<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Interfaces;

use App\Models\User;

interface RequiresPermissions
{
    public function requiresPermissions(): array;

    public function canView(User $user): bool;
}
