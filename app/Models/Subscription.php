<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class Subscription
 * @package App\Models
 * @version May 16, 2018, 11:40 am UTC
 *
 * @property integer product_id
 * @property integer team_id
 * @property integer parent_id
 * @property string currency
 * @property integer price
 * @property json payment_details
 * @property string payment_id
 * @property string payment_provider
 * @property integer users
 * @property string type
 * @property timestamp start
 * @property timestamp expiry
 * @property boolean active
 */
class Subscription extends Model implements Interfaces\Exportable
{
    use HasRoles;

    protected $dates = [
        'start',
        'expiry'
    ];

    public $fillable = [
        'subscribable_id',
        'subscribable_type',
        'team_id',
        'parent_id',
        'currency',
        'price',
        'payment_details',
        'payment_id',
        'payment_provider',
        'users',
        'type',
        'start',
        'expiry',
        'active',
        'lead_id',
        'distribution_count',
        'source_reason',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'subscribable_id' => 'integer',
        'team_id' => 'integer',
        'parent_id' => 'integer',
        'currency' => 'string',
        'price' => 'integer',
        'payment_id' => 'string',
        'payment_provider' => 'string',
        'users' => 'integer',
        'type' => 'string',
        'active' => 'boolean',
        'lead_id' => 'string',
        'distribution_count' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'subscribable_id' => 'required|integer',
        'subscribable_type' => 'required|string',
        'team_id' => 'required|exists:teams,id',
        'payment_provider' => 'required',
        'type' => 'required',
        'start' => 'required',
        'expiry' => 'required'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function subscribable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeCurrent($query)
    {
        return $query
            ->where('start', '<', now())
            ->where('expiry', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where(function ($query) {
            $query->orWhere('active', '=', 0);
            $query->orWhere(function ($query) {
                $query->where('start', '<=', Carbon::now()->addWeeks(10));
                $query->where('expiry', '>=', Carbon::now()->subMonth());
                $query->where('expiry', '<=', Carbon::now()->addMonth());
            });
            $query->orWhere(function ($query) {
                $query->whereHas('parent', function ($query) {

                    $query->where('expiry', '>=', Carbon::now()->subMonth());
                    $query->where('expiry', '<=', Carbon::now()->addMonth());


                    $query->where('active', '=', 1);
                });
            });
        });
    }

    public function getRenewal()
    {
        $start = $this->expiry->copy();
        $start->addDays(1);
        $start->second = $this->start->second;
        $start->minute = $this->start->minute;
        $start->hour = $this->start->hour;
        if (($this->subscribable instanceof Product) && $this->subscribable->duration) {
            $end =  $start->copy();
            $end->addMonths($this->subscribable->duration);
            $end->second = $this->expiry->second;
            $end->minute = $this->expiry->minute;
            $end->hour = $this->expiry->hour;
        } else {
            $diff = $this->start->diffInMonths($this->expiry);
            if ($diff <= 14) {
                $diff = 1;
            } elseif ($diff <= 26) {
                $diff = 2;
            } else {
                $diff = 3;
            }
            $end = $start->copy();
            $end->addMonthsNoOverflow($diff);
            $end->subDay();
            $end->second = $this->expiry->second;
            $end->minute = $this->expiry->minute;
            $end->hour = $this->expiry->hour;
        }

        return [
            'subscribable_id' => $this->subscribable_id,
            'subscribable_type' => $this->subscribable_type,
            'team_id' => $this->team_id,
            'currency' => $this->currency,
            'price'  => $this->price,
            'payment_details'  => '',
            'payment_id' => '',
            'payment_provider' => $this->payment_provider,
            'source_reason' => $this->source_reason,
            'users'  => $this->users,
            'type'  => $this->type,
            'start' => $start,
            'expiry' => $end,
            'active' => 0
        ];
    }
    public function createRenewal()
    {
        return $this->children()->create($this->getRenewal());
    }

    /* Scopes */

    public function scopeIsEnabled($query) // "is active" would be misleading because the active field doesn't necessarily mean the subscription is active.
    {
        return $query
            ->where('active', true)
            ->current();
    }

    public function guardName()
    {
        // this is required for the permissions package
        return 'web';
    }
}
