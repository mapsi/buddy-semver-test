<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TeamFeaturesUpdated;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UpdateUserFeatures
{
    public function handle(TeamFeaturesUpdated $event)
    {
        $event->model->members->each(function (User $user) {

            foreach (Brand::all() as $brand) {
                // invalidate cache
                $cacheKey = generate_cache_key((string) $user->id, $brand);
                $tags = $user->getFeatureCacheTags();
                logger('Invalidating cache key:', ['key' => $cacheKey, 'tags' => $tags]);
                Cache::tags($tags)->forget($cacheKey);

                // prime cache
                $user->getFeatureAccess($brand);
            }
        });
    }
}
