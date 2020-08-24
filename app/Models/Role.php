<?php

namespace App\Models;

use App\Models\Interfaces\Brandable;
use App\Models\Traits\BrandableTrait;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole implements Brandable
{
    use BrandableTrait;
}
