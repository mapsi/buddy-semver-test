<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ViewCount extends Model implements Interfaces\Exportable
{
    public $fillable = [
        'count',
        'brand_id',
        'countable_id',
        'countable_type',
        'start',
        'end',
    ];


    public function scopeLastMonth($query)
    {
        return $query->where('start', '>=', Carbon::now()->subMonth());
    }

    public function countable()
    {
        return $this->morphTo('countable');
    }
}
