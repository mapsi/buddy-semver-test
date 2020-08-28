<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubscriptionLevel extends Role
{
    const BRANDS_USING_THIS = [
        'grr', 'gcr'
    ];

    public static function usingThis(string $brandName)
    {
        return in_array($brandName, self::BRANDS_USING_THIS);
    }

    public $fillable = [
        'name',
        'description',
        'brand_id',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'brand_id' => 'integer',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required',
        'description' => 'string',
        'brand_id' => 'required|exists:brands,id',
    ];

    protected static function boot()
    {
        parent::boot();

        // auto-sets values on creation
        static::saving(function ($query) {
            $query->guard_name = 'web';
        });

        static::addGlobalScope('branded', function (Builder $builder) {
            $builder->whereNotNull('brand_id');
        });
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function features()
    {
        return $this->permissions();
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function getBrandsAttribute(): Collection
    {
        return collect([$this->brand]);
    }
}
