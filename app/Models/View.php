<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class View extends Model implements Interfaces\Exportable
{
    public $fillable = [
        'user_id',
        'brand_machine_name',
        'routable_id',
        'routable_type',
        'device',
        'is_free',
        'is_full_read',
        'browser',
        'platform',
        'version',
        'is_desktop',
        'is_phone',
        'is_robot',
        'ip',
        'route',
        'type'
    ];

    public function scopeLastMonth($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subMonth());
    }

    public function scopeFreeRead($query)
    {
        return $query->where('type', 'read')->where('is_free', 1)->where('is_full_read', 1);
    }

    public function routable()
    {
        return $this->morphTo('routable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
