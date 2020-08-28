<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Products
 * @package App\Models
 * @version May 14, 2018, 3:27 pm UTC
 *
 * @property varchar name
 * @property string description
 * @property integer users
 * @property varchar type
 */
class Product extends Model
{


    public $table = 'products';


    protected $dates = [];


    public $fillable = [
        'name',
        'description',
        'users',
        'type',
        'duration',
        'feature_reports'
    ];

    public $feature_articles = true;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'description' => 'string',
        'users' => 'integer',
        'feature_reports' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required',
        'type' => 'required'
    ];

    const SUPPORTED_FEATURES = [
        'reports'
    ];
    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'product_brands', 'product_id', 'brand_id');
    }
    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /* Scopes */

    public function scopeIsForBrand($query, Brand $brand)
    {
        return $query->whereHas('brands', function ($query) use ($brand) {
            return $query->where('id', $brand->id);
        });
    }


    public function hasFeature($feature)
    {
        $property = 'feature_' . $feature;
        return (bool) $this->$property;
    }
}
