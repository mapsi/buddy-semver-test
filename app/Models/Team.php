<?php

namespace App\Models;

use App\Models\Interfaces\HasAccessToFeatures;
use App\Models\Traits\HasAccessToFeatures as TraitsHasAccessToFeatures;
use Illuminate\Database\Eloquent\Model as Model;

/**
 * Class Team
 * @package App\Models
 * @version May 16, 2018, 1:56 pm UTC
 *
 * @property string name
 * @property string vat
 * @property string address1
 * @property string address2
 * @property string address3
 * @property string postcode
 * @property string city
 * @property string state
 * @property string country_id
 */
class Team extends Model implements Interfaces\Exportable, HasAccessToFeatures
{
    use TraitsHasAccessToFeatures;

    const COMPANY_TYPES = [
        'Intellectual Property owner (non-operating)',
        'Intellectual Property owner (operating company)',
        'Intermediary/broker',
        'Finance/investment',
        'Service provider',
        'Law firm/attorney firm',
        'Academic/research',
        'Industry body/organisation',
        'Patent and/or trademark office',
        'Government agency',
    ];

    public $fillable = [
        'name',
        'address1',
        'address2',
        'address3',
        'postcode',
        'city',
        'state',
        'country_id',
        'billing_address1',
        'billing_address2',
        'billing_address3',
        'billing_postcode',
        'billing_city',
        'billing_state',
        'billing_country_id',

        'company',
        'notes',
        'firm_ref',

        'user_id',
        'vat',
        'telephone',
        'company_type'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'vat' => 'string',
        'address1' => 'string',
        'address2' => 'string',
        'address3' => 'string',
        'postcode' => 'string',
        'city' => 'string',
        'state' => 'string',
        'country_id' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required',
        'vat' => 'nullable',
        'address1' => 'required',
        'address2' => 'nullable',
        'city' => 'required',
        'state' => 'nullable',
        'country_id' =>  'required',


    ];
    public function primary()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
    public function currentSubscriptions()
    {
        return $this->subscriptions->filter(function ($item) {
            return \Carbon\Carbon::now()->subMonth()->lt($item->expiry)
                && \Carbon\Carbon::now()->gt($item->start);
        });
    }
    public function isGroup()
    {
        return $this->members->count() > 1;
    }
    public function scopeIsGroup($query)
    {
        $query->has('members', '>', 1);
    }
    public function scopeIsIndividual($query)
    {
        $query->has('members', '=', 1);
    }
    public function scopeIsSubscriber($query, $brand)
    {
        return $query->whereHas('subscriptions', function ($query) use ($brand) {
            $query->where('expiry', '<=', \Carbon\Carbon::now()->addMonth())
                ->where('start', '<', \Carbon\Carbon::now())
                ->where('active', '=', 1)
                ->whereHas('product', function ($query) use ($brand) {
                    $query->whereHas('brands', function ($query) use ($brand) {
                        $query->where('id', '=', $brand->id);
                    });
                });
        });
    }

    public function getFeatureCacheTags(): array
    {
        return ['features', 'teams'];
    }

    public function getFeatureAccess(Brand $brand = null): array
    {
        if (! $this->id) {
            return [];
        }

        if (! $brand) {
            $brand = resolve(Brand::class);
        }

        // cache for 5 mins
        return cacheStuff($this->id, 5, function () {
            $features = [];

            $enabledSubscriptions = $this->subscriptions()->isEnabled()->get();

            logger("{$this->name} has {$enabledSubscriptions->count()} enabled subscriptions.");

            $enabledSubscriptions->each(function (Subscription $subscription) use (&$features) {

                $permissions = $subscription->getAllPermissions()->map(function (Permission $permission) {
                    return $permission->name;
                })->values()->all();
                $features = array_merge($features, $permissions);
            });

            logger("{$this->name} has the following subscriptions:", $features);

            return $features;
        }, $this->getFeatureCacheTags(), $brand);
    }
}
