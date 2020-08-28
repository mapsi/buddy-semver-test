<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    public $fillable = [
        'currency',
        'price',
    ];

    public function getCurrencySymbolAttribute()
    {
        return config('currencies.list')[$this->currency]['symbol'];
    }

    public function getCurrencyCodeAttribute()
    {
        return $this->currency;
    }

    public function priceable()
    {
        return $this->morphTo();
    }
}
