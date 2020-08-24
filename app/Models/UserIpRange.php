<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserIpRange extends Model
{
    public $fillable = [
        'range_from',
        'range_to'
    ];

    function user()
    {
         return $this->belongsTo(User::class);
    }

    public function getIpFromAttribute()
    {
        return inet_ntop($this->range_from);
    }

    public function getIpToAttribute()
    {
        return inet_ntop($this->range_to);
    }

    public function setRangeFromAttribute($value)
    {
        $this->attributes['range_from'] = ($value = inet_pton($value)) ? $value : '';
    }

    public function setRangeToAttribute($value)
    {
        $this->attributes['range_to'] = ($value = inet_pton($value)) ? $value : '';
    }

    public function scopeValidIp($query, $ip)
    {
        $ip = inet_pton($ip);

        if ($ip != false) {
            return $query->where([
                ['range_from','<=', $ip],
                ['range_to','>=', $ip]
            ])->get();
        }

        return collect();
    }

    public function hasUserIds(array $userIds)
    {
        return in_array($this->user_id, $userIds);
    }
}
