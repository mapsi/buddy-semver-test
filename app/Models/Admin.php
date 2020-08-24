<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Description of Admin
 *
 * @author mark
 */
class Admin extends User
{
    protected static function boot()
    {
        parent::boot();

        // Always order by weight ascending.
        static::addGlobalScope('admin', function (Builder $builder) {
            $builder->where('admin', '=', 1);
        });
    }
}
