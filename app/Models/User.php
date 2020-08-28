<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Jrean\UserVerification\Traits\VerifiesUsers;
use Jrean\UserVerification\Traits\UserVerification;
use App\Notifications\ResetPassword;
use Newsletter;
use App\Events\UserSaved;
use App\Notifications\WelcomeEmail;
use Illuminate\Support\Facades\DB;
use App\Models\Interfaces\HasAccessToFeatures;
use App\Models\Traits\HasAccessToFeatures as TraitsHasAccessToFeatures;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Attributes
 * @property int id
 * @property string name
 * @property string email
 * @property string password
 * @property int admin
 * @property string remember_token
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int verified
 * @property string verification_token
 * @property int wtr_daily
 * @property int wtr_weekly
 * @property int iam_weekly
 * @property string job_function
 * @property string job_title
 * @property string friendly_id
 * @property string notes
 * @property string company
 *
 * Accessors
 * @property string title
 * @property string forename
 * @property string surname
 * @property string name_parts
 *
 * Relations
 * @property Collection teams
 * @property Collection manages
 * @property Collection ips
 * @property Collection brands
 *
 */
class User extends Authenticatable implements HasAccessToFeatures, JWTSubject
{
    use Notifiable;
    use VerifiesUsers;
    use UserVerification;
    use TraitsHasAccessToFeatures;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $dispatchesEvents = [
        'saved' => UserSaved::class,
    ];
    protected $dates = ['deleted_at'];
    public $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'admin',
        'wtr_weekly',
        'wtr_daily',
        'iam_weekly',
        'daily',
        'verified',
        'job_type',
        'job_function',
        'friendly_id',
        'company',
        'notes'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'remember_token' => 'string',
        'friendly_id' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'ips.*.name' => 'required_with:ips.*.range',
        'ips.*.range' => ['nullable', 'CDIR', 'required_with:ips.*.name'],
        'name' => 'required|string|max:255',
        'email' => ['required', 'string', 'email', 'max:255'],
        'password' => 'nullable|string|min:6'
    ];

    public static $userLevels = [
        0 => 'User',
        1 => 'Admin',
        2 => 'Editor',
        3 => 'Subscription Manager'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function manages()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ips()
    {
        return $this->hasMany(IpRange::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ipRanges()
    {
        return $this->hasMany(UserIpRange::class);
    }

    /**
     * Accessor method to return the entire set of ip ranges for the user
     * @return string the ip ranges for the user
     */
    public function getAllIpRangesAttribute(): string
    {
        $ranges = "";

        foreach ($this->ipRanges as $range) {
            if ($range->ip_from === $range->ip_to) {
                $ranges .= $range->ip_from . PHP_EOL;
            } else {
                $ranges .= $range->ip_from . '-' . $range->ip_to . PHP_EOL;
            }
        }

        return $ranges;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lbrDetail()
    {
        return $this->hasOne(UserLbrDetail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function brands()
    {
        return $this->belongsToMany(Brand::class)->withPivot('username', 'password', 'remember_token');
    }

    public function getBrandUsernameAttribute($brand = null)
    {
        if (! $brand) {
            $brand = resolve(Brand::class);
        }

        $brandUser = $this->brands->first(function ($item) use ($brand) {
            return $item->id === $brand->id;
        });

        if ($brandUser) {
            return $brandUser->pivot->username;
        } else {
            return null;
        }
    }

    public function hasPasswordForBrand($brand = null)
    {
        if (! $brand) {
            $brand = resolve(Brand::class);
        }

        $brandUser = $this->brands->first(function ($item) use ($brand) {
            return $item->id === $brand->id;
        });

        if ($brandUser) {
            return $brandUser->pivot->password ? true : false;
        } else {
            return false;
        }
    }

    /* Scopes */
    public function scopeSubscribesToBrand($query, Brand $brand)
    {
        $query->whereHas(
            'teams.subscriptions',
            function ($query) {
                $query->isEnabled();
            }
        );

        $query->whereHas(
            'teams.subscriptions.product',
            function ($query) use ($brand) {
                $query->isForBrand($brand);
            }
        );

        return $query;
    }

    public function scopeReceievesEmail($query, Brand $brand, string $type)
    {
        $column_name = $brand->machine_name . '_' . $type;

        if (! in_array($column_name, ['wtr_daily', 'wtr_weekly', 'iam_weekly'])) {
            throw new Exception('There is no user mail preference for: ' . $column_name);
        }

        return $query->where($column_name, true);
    }
    /* Helpers */

    public function isAdmin(): bool
    {
        if (empty($this->admin)) {
            return false;
        }

        return self::$userLevels[$this->admin] === 'Admin' ? true : false;
    }

    public function isEditor(): bool
    {
        return $this->isAdmin() || (self::$userLevels[$this->admin] === 'Editor' ? true : false);
    }

    public function isSubscriptionManager(): bool
    {
        return self::$userLevels[$this->admin] === 'Subscription Manager' ? true : false;
    }

    public function getUserLevelAttribute()
    {
        return (string) self::$userLevels[$this->admin];
    }

    public function isSubscriber($feature = false)
    {
        $brand = resolve(Brand::class);
        if (SubscriptionLevel::usingThis($brand->machine_name)) {
            return count($this->getFeatureAccess());
        }
        return $this->isSubscriberOfBrand($brand, $feature);
    }
    public function isSubscriberOfBrand($brand, $feature = false)
    {
        if (SubscriptionLevel::usingThis($brand->machine_name)) {
            return count($this->getFeatureAccess());
        }
        if (! $this->relationLoaded('teams')) {
            $this->load('teams.subscriptions.subscribable');
        }
        return $this->teams->filter(function ($team) use ($brand, $feature) {
            return $team->subscriptions->where('active', 1)->filter(function ($item) use ($brand, $feature) {

                //if there is no brand just say no
                return $brand
                    && \Carbon\Carbon::now()->lte($item->expiry)
                    && \Carbon\Carbon::now()->gte($item->start)
                    //if there is no product just say no
                    && $item->subscribable
                    && (! $feature || $item->subscribable->hasFeature($feature))
                    && $item->subscribable->brands->where('id', $brand->id)->count();
            })->count() >= 1;
        })->count() >= 1;
    }

    public function newsletterCheckSubscriptions()
    {
        if (config('newsletter.apiKey') && ($this->iam_weekly || $this->wtr_daily || $this->wtr_weekly)) {
            Newsletter::subscribeOrUpdate(
                $this->email,
                ['FNAME' => $this->forename, 'LNAME' => $this->surname],
                'subscribers',
                [
                    'interests' => $this->newsletterInterests()
                ]
            );
        }
    }
    public function newsletterInterests()
    {
        return [
            config('newsletter.interests.iam_weekly') => $this->iam_weekly == 1,
            config('newsletter.interests.wtr_daily') => $this->wtr_daily &&
                $this->isSubscriberOfBrand(Brand::where('machine_name', 'wtr')->first()),
            config('newsletter.interests.wtr_weekly') => $this->wtr_weekly == 1
        ];
    }

    public function save(array $options = array())
    {

        $s = parent::save($options);
        $this->newsletterCheckSubscriptions();
        return $s;
    }

    public function views()
    {
        return $this->hasMany(View::class, 'user_id');
    }

    public function delete()
    {
        if (config('newsletter.apiKey')) {
            Newsletter::unsubscribe($this->email);
        }
        $this->views()->delete();
        return parent::delete();
    }

    public function getTitleAttribute()
    {
        return $this->name_parts['title'];
    }

    public function getForenameAttribute()
    {
        return $this->name_parts['forename'];
    }

    public function getSurnameAttribute()
    {
        return $this->name_parts['surname'];
    }

    public function getNamePartsAttribute()
    {
        $parts = explode(' ', $this->name);

        if (count($parts) === 1) {
            return [
                'title' => '',
                'forename' => '',
                'surname' => $this->name,
            ];
        } elseif (count($parts) == 2) {
            return [
                'title' => '',
                'forename' => $parts[0],
                'surname' => $parts[1],
            ];
        } else {
            return [
                'title' => array_shift($parts),
                'forename' => array_shift($parts),
                'surname' => implode(' ', $parts)
            ];
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendWelcomeNotification($token)
    {
        $this->notify(new WelcomeEmail($token));
    }

    public function setRememberToken($token)
    {
        $brand = resolve(Brand::class);

        if ($brand) {
            DB::table('brand_user')
                ->where('brand_id', $brand->id)
                ->where('user_id', $this->id)
                ->update([
                    'remember_token' => $token,
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
        }
    }

    public function getRememberToken()
    {
        if ($token = parent::getRememberToken()) {
            return $token;
        }

        $brand = resolve(Brand::class);

        if ($brand) {
            $brandUser = DB::table('brand_user')
                ->where('brand_id', $brand->id)
                ->where('user_id', $this->id)
                ->first();

            if (! $brandUser) {
                return null;
            }

            return (string) $brandUser->remember_token;
        }
    }

    public function getFeatureCacheTags(): array
    {
        return ['features', 'users'];
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

            $this->teams->each(function (Team $team) use (&$features) {
                $features = array_merge($features, $team->getFeatureAccess());
            });

            return $features;
        }, $this->getFeatureCacheTags(), $brand);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey() . '@' . get_host_config('host');
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        // TODO: refresh token when permissions change
        return [
            'features' => $this->getFeatureAccess(),
        ];
    }
}
