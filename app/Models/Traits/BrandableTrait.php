<?php

namespace App\Models\Traits;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BrandableTrait
{
    public static function bootBrandableTrait()
    {
        static::addGlobalScope('brand', function (Builder $builder) {
            if (! $brand = resolve(Brand::class)) {
                return;
            }

            $builder->whereHas('brand', function ($query) use ($brand) {
                $query->where('brands.id', $brand->id);
            });
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
