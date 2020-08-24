<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Coupon
 * @package App\Models
 * @version May 14, 2018, 11:27 pm UTC
 *
 * @property string code
 * @property double percentage
 * @property integer limit
 */
class Coupon extends Model
{


    public $table = 'coupons';


    protected $dates = ['expires_at'];


    public $fillable = [
        'code',
        'percentage',
        'limit',
        'used',
        'product_id',
        'expires_at'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'string',
        'percentage' => 'double',
        'limit' => 'integer',
        'used' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required',
        'percentage' => 'required',
        'limit' => 'integer',
        'used' => 'integer'
    ];

    function product()
    {
        return $this->belongsTo(Product::class);
    }
}
