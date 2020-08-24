<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionFeaturesUpdated;
use App\Events\TeamFeaturesUpdated;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class UpdateTeamFeatures
{
    public function handle(SubscriptionFeaturesUpdated $event)
    {
        $team = $event->model->team;

        foreach (Brand::all() as $brand) {
            // invalidate cache
            $cacheKey = generate_cache_key((string) $team->id, $brand);
            $tags = $team->getFeatureCacheTags();
            logger('Invalidating cache key:', ['key' => $cacheKey, 'tags' => $tags]);
            Cache::tags($tags)->forget($cacheKey);

            // prime cache
            $team->getFeatureAccess($brand);
        }

        event(new TeamFeaturesUpdated($team));
    }
}
