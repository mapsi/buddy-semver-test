<?php

namespace App\Models;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;

/**
 * The Route model maps a branded path to the model in Laravel.
 */
class Route extends Model implements Responsable
{
    public $timestamps = false;

    protected $fillable = [
        'path',
        'is_canonical',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public static function findByPath($path)
    {
        //optimisation on getting routes
        static $results = [];
        if (isset($results[$path])) {
            return $path;
        }

        if (! $brand = resolve(Brand::class)) {
            return null;
        }

        /** @todo This should be based on the Branded interface */
        $routedModel = self::checkForBrandedPath($path, $brand, $results);

        if (! $routedModel) {
            $routedModel = self::checkForUnbrandedPath($path, $results);
        }

        request()->merge([
            'route_model' => $routedModel,
            'model' => optional($routedModel)->routable,
        ]);

        return $routedModel;
    }

    /**
     * @param       $path
     * @param       $brand
     * @param array $results
     * @return mixed
     */
    protected static function checkForBrandedPath($path, $brand, array $results)
    {
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        $branded_path = '/' . $brand->machine_name . $path;

        $routedModel = $results[$path] = static::where('path', $branded_path)
            ->orWhere('path', '/wtr-iam' . $path)// A dirty hack before launch.
            ->first();

        return $routedModel;
    }

    /**
     * @param       $path
     * @param       $brand
     * @param array $results
     * @return mixed
     */
    protected static function checkForUnBrandedPath($path, array $results)
    {
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        $routedModel = $results[$path] = static::where('path', $path)->first();

        return $routedModel;
    }

    public function routable()
    {
        return $this->morphTo();
    }

    public function toResponse($request)
    {
        if ($this->routable) {
            return $this->routable->toResponse($request);
        } else {
            return abort(404);
        }
    }
}
