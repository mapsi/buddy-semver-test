<?php

namespace App\Repositories;

use App\Models\IpRange;
use App\Models\UserIpRange;
use App\Models\User;
use InfyOm\Generator\Common\BaseRepository;
use App\Classes\IpCheck;
use App\Models\Brand;
use App\Models\UserLbrDetail;
use Illuminate\Support\Facades\DB;

/**
 * Class UserRepository
 * @package App\Repositories
 * @version April 13, 2018, 11:51 am UTC
 *
 * @method User findWithoutFail($id, $columns = ['*'])
 * @method User find($id, $columns = ['*'])
 * @method User first($columns = ['*'])
 */
class UserRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => 'like',
        'friendly_id' => '=',
        'id' => '=',
        'company' => 'like'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return User::class;
    }

    protected function fixAttributes(array $attributes)
    {
        if (! isset($attributes['admin'])) {
            $attributes['admin'] = 0;
        }
        if (! isset($attributes['iam_weekly'])) {
            $attributes['iam_weekly'] = 0;
        }
        if (! isset($attributes['wtr_weekly'])) {
            $attributes['wtr_weekly'] = 0;
        }
        if (! isset($attributes['wtr_daily'])) {
            $attributes['wtr_daily'] = 0;
        }
        return $attributes;
    }

    protected function createOrUpdateUserLbrDetails($attributes, $user)
    {
        if ($attributes['lbr_user_id'] || $attributes['lbr_organisation_id'] || $attributes['lbr_account_id']) {
            $userLbrDetail = UserLbrDetail::firstOrNew(['user_id' => $user->id]);
            $userLbrDetail->lbr_user_id = $attributes['lbr_user_id'];
            $userLbrDetail->lbr_organisation_id = $attributes['lbr_organisation_id'];
            $userLbrDetail->lbr_account_id = $attributes['lbr_account_id'];
            $userLbrDetail->save();
        }
    }

    public function create(array $attributes)
    {
        if (empty($attributes['password'])) {
            unset($attributes['password']);
        } else {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        $user = parent::create($this->fixAttributes($attributes));
        $this->updateBrandCredentials($attributes, $user, true);
        $this->createOrUpdateUserLbrDetails($attributes, $user);
        return $user;
    }

    public function update(array $attributes, $id)
    {
        if (empty($attributes['password'])) {
            unset($attributes['password']);
        } else {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        $user =  parent::update($this->fixAttributes($attributes), $id);

        $this->updateBrandCredentials($attributes, $user, false);
        $this->createOrUpdateUserLbrDetails($attributes, $user);
        return $user;
    }

    public function where($field, $condition, $value)
    {
        $this->applyConditions([[$field, $condition, $value]]);
    }

    public function updateRelations($user, $attributes)
    {
        $this->updateIPRanges($user, $attributes);

        return parent::updateRelations($user, $attributes);
    }

    private function updateIPRanges($user, $attributes)
    {
        $user->ipRanges()->delete();

        if (isset($attributes['ip_ranges'])) {
            preg_match_all("/(?'ipfrom'\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b|\b(([a-fA-F]|[a-fA-F][a-fA-F0-9\-]*[a-fA-F0-9])\.)*([A-Fa-f]|[A-Fa-f][A-Fa-f0-9\-]*[A-Fa-f0-9])\b)(-(?'ipto'\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b|\b(([a-fA-F]|[a-fA-F][a-fA-F0-9\-]*[a-fA-F0-9])\.)*([A-Fa-f]|[A-Fa-f][A-Fa-f0-9\-]*[A-Fa-f0-9])\b))?/m", $attributes['ip_ranges'], $matches);

            $ipsFrom = collect($matches['ipfrom']);
            $ipsTo = collect($matches['ipto']);

            $ranges = collect();

            $ipsFrom->each(function ($ipFrom, $key) use ($ranges, $ipsTo) {
                $ips = collect();
                $ips->put('ipfrom', $ipFrom);
                $ips->put('ipto', $ipsTo->get($key) ?: $ipFrom);
                $ranges->put($key, $ips);
            });

            $this->addRanges($ranges, $user);
        }
    }

    private function addRanges($ranges, $user)
    {
        $ranges->each(function ($range) use ($user) {
            $userIpRange = new UserIpRange();
            $userIpRange->range_from = $range->get('ipfrom');
            $userIpRange->range_to = $range->get('ipto');

            $user->ipRanges()->save($userIpRange);
        });
    }

    protected function updateBrandCredentials($attributes, $user, $newRecord = false)
    {
        foreach ($attributes['brands'] as $brand_id) {
            $username = $attributes['usernames'][$brand_id];

            if (empty($attributes['passwords'][$brand_id])) {
                $password = null;
            } else {
                $password = bcrypt($attributes['passwords'][$brand_id]);
            }

            $query = DB::table('brand_user')
                ->where('brand_id', $brand_id)
                ->where('user_id', $user->id);

            if ($newRecord) {
                $query->update(['created_at' => \Carbon\Carbon::now()]);
            }

            $query->update([
                'username' => $username,
                'updated_at' => \Carbon\Carbon::now(),
            ]);

            if ($password) {
                $query->update([
                    'password' => $password,
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            }
        }
    }

    public function delete($id)
    {
        UserLbrDetail::where('user_id', $id)->delete();
        return parent::delete($id);
    }

    public function migratePassword(User $user): bool
    {
        if ($user->admin != 0) {
            // don't migrate admins
            return false;
        }

        if (empty($user->password)) {
            // user has already been migrated
            return false;
        }

        $success = false;

        DB::transaction(function () use ($user, &$success) {
            // 1. subscriber of brand
            $subscriberOfBrands = [];
            foreach (Brand::all() as $brand) {
                if ($user->isSubscriberOfBrand($brand)) {
                    $subscriberOfBrands[] = $brand->id;
                }
            }
            // 2. registered with brand
            $registeredWith = [];
            foreach ($user->brands as $brand) {
                $registeredWith[] = $brand->id;
            }
            // 3. copy passwords
            $results = array_unique(array_merge($subscriberOfBrands, $registeredWith));

            foreach ($results as $result) {
                $query = DB::table('brand_user')
                    ->where('brand_id', $result)
                    ->where('user_id', $user->id);

                if ($query->get()->count() > 0) {
                    //update
                    $query->update([
                        'password' => $user->password,
                        'remember_token' => $user->remember_token,
                        'created_at' =>  \Carbon\Carbon::now(),
                        'updated_at' => \Carbon\Carbon::now(),
                    ]);
                } else {
                    //insert
                    $user->brands()->attach($result, [
                        'password' => $user->password,
                        'remember_token' => $user->remember_token,
                        'created_at' =>  \Carbon\Carbon::now(),
                        'updated_at' => \Carbon\Carbon::now(),
                    ]);
                }
            }
            // leave global passwords in place for admins
            if (! $user->admin) {
                $user->password = null;
                $user->remember_token = null;
            }
            $user->save();

            $success = true;
        });

        return $success;
    }
}
