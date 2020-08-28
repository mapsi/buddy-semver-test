<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\EloquentUserProvider as UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomUserProvider extends UserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        $success = false;

        //try with global password (only admins allowed to use global passwords)
        if ($user->admin) {
            $success =  $this->hasher->check($plain, $user->getAuthPassword());
        }

        //try with brand specific password
        if ($success === false) {
            if ($user->password) {
                $repository = app(UserRepository::class);
                $repository->migratePassword($user);
            }

            if ($brand = resolve(Brand::class)) {
                $brandUser = DB::table('brand_user')
                    ->where('brand_id', $brand->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($brandUser) {
                    $brand_password = $brandUser->password;
                    $success =  $this->hasher->check($plain, $brand_password);
                }
            }

            if (
                ! $success && ! empty($brandUser)
                && md5($credentials['password']) === $brandUser->legacy_password_hash
            ) {
                $newPasswordHash = Hash::make($credentials['password']);

                DB::table('brand_user')
                    ->where('brand_id', $brand->id)
                    ->where('user_id', $user->id)
                    ->update([
                        'password' => $newPasswordHash,
                        'legacy_password_hash' => null,
                        'updated_at' => \Carbon\Carbon::now(),
                        ]);

                $success = true;
            }
        }

        return $success;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (! $credentials) {
            return null;
        }

        // attempt to fetch by email
        $user = User::with('brands')->where('email', $credentials['email'])->first();

        $brand = resolve(Brand::class);
        if (! $brand) {
            // we don't have a brand cos we're in admin
            return $user;
        }

        if (Arr::get($credentials, 'token')) {
            // if token is found, it means we're registering a user with another brand
            return $user;
        }

        if ($user) {
            $brandFound = $user->brands->first(function (Brand $userBrand) use ($brand) {
                return $userBrand->id === $brand->id;
            });

            // couldn't find a brand association...
            if (! $brandFound) {
                return null;
            }
            return $user;
        }

        //attempt to fetch by brand specific username
        $brand_user = DB::table('brand_user')
            ->where('brand_id', $brand->id)
            ->where('username', $credentials['email'])
            ->first();

        if ($brand_user) {
            $user = User::where('id', $brand_user->user_id)->first();
        }

        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        if ($model = parent::retrieveByToken($identifier, $token)) {
            $repository = app(UserRepository::class);
            $repository->migratePassword($model);

            return $model;
        }

        if ($brand = resolve(Brand::class)) {
            $brand_user = DB::table('brand_user')
                ->where('brand_id', $brand->id)
                ->where('user_id', $identifier)
                ->first();

            //attempt to retrieve by brand-specific info
            if ($brand_user) {
                $retrievedModel = User::where('id', $brand_user->user_id)->first();

                $rememberToken = $retrievedModel->getRememberToken();

                return $rememberToken && hash_equals($rememberToken, $token)
                    ? $retrievedModel : null;
            }
        }
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
    }
}
