<?php

namespace App\Models\Traits;

use App\Models\Route;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Routable
{
    public static function bootRoutable()
    {
        static::addGlobalScope('route', function (Builder $builder) {
            $builder->with('route');
        });
    }

    public function route(): MorphOne
    {
        return $this->morphOne(Route::class, 'routable')->where('is_canonical', true);
    }

    public function routes(): MorphMany
    {
        return $this->morphMany(Route::class, 'routable');
    }

    public function getCanonicalUrlAttribute()
    {
        // @todo if ($this instanceof Brandable) { This needs to be added after because models can not have the brand prefix
        return $this->route ? '/' . implode('/', array_slice(explode('/', $this->route->path), 2)) : null;
        //}
    }

    public function addRoute(string $path, bool $is_canonical = false)
    {
        if ($is_canonical) {
            $this->routes()->update(['is_canonical' => false]);
        }

        try {
            $this->routes()->updateOrCreate(['path' => $path], ['is_canonical' => $is_canonical]);
        } catch (\Exception $e) {
            if ($e->getCode() === 23000) {
                // Route exists, do nothing
                logger("Could not add route $path - already exists");
            }
        }

        return $this->routes();
    }

    public function views(): MorphMany
    {
        return $this->morphMany(\App\Models\View::class, 'routable');
    }
}
