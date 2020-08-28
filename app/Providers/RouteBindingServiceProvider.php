<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryIndividual;
use App\Models\Email;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteBindingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Brand $brand
     * @return void
     */
    public function boot(Brand $brand = null)
    {
        $this->bindEmail($brand);

        $this->bindDirectoryPreview();

        $this->bindJurisdiction();

        $this->bindFirm();

        $this->bindIndividual();

        // Route::model('subregion', Region::class);

        // $this->bindSurveyEdition();

        // $this->bindSurveyOrganizationProfile();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Check this email belongs to this brand
     *
     * @param Brand|null $brand
     */
    protected function bindEmail($brand)
    {
        Route::bind('email', function ($value, $route) use ($brand) {
            if (! $brand) {
                return Email::findOrFail($value);
            }

            if (strpos($route->getName(), 'admin.') === 0) {
                return Email::findOrFail($value);
            }

            return Email::where('brand_id', $brand->id)->findOrFail($value);
        });
    }

    /**
     *
     */
    protected function bindDirectoryPreview()
    {
        Route::bind('directory_preview', function ($value, $route) {
            $directory = Directory::where('slug', $route->parameter('directory_preview') . '/preview')
                ->where('preview', '=', 1)
                ->firstOrFail();

            return $directory;
        });
    }

    /**
     * Check this jurisdiction belongs to this directory
     */
    protected function bindJurisdiction()
    {
        Route::bind('jurisdiction', function ($value, $route) {
            if (! $route->hasParameter('directory') && ! $route->hasParameter('directory_preview')) {
                abort(404);
            }
            if (! is_object($route->parameter('directory', $route->parameter('directory_preview')))) {
                $directory = Directory::where('slug', $route->parameter('directory', $route->parameter('directory_preview') . '/preview'))->firstOrFail();
            } else {
                $directory = $route->parameter('directory', $route->parameter('directory_preview'));
            }

            return $directory->jurisdictions()->where('slug', $value)->firstOrFail();
        });
    }

    /**
     * Check this firm belongs to this directory
     */
    protected function bindFirm()
    {
        Route::bind('firm', function ($value, $route) {
            if (! $route->hasParameter('directory')) {
                abort(404);
            }

            $directory = Directory::where('slug', $route->parameter('directory'))->firstOrFail();

            return $directory->firms()->where('slug', $value)->firstOrFail();
        });
    }

    /**
     * Check this individual belongs to this directory
     */
    protected function bindIndividual()
    {
        Route::bind('individual', function ($value, $route) {
            if (! $route->hasParameter('directory')) {
                abort(404);
            }

            return DirectoryIndividual::whereHas('directory', function ($directory) use ($route) {
                return $directory->where('slug', $route->parameter('directory'));
            })->where('slug', $value)->firstOrFail();
        });
    }

    /**
     * Edition needs to belong to a Series
     */
    protected function bindSurveyEdition()
    {
        Route::bind('edition', function ($value, $route) {
            if (! $route->hasParameter('series')) {
                abort(404);
            }

            $service = brandService();
            $search = $service->newSearch();

            $search->setSlug($value);
            $result = $service->run($search);

            $edition = $result->hydrate()[0];

            Cache::tags('edition')->forever($value, $edition->getId());

            return $edition;
        });
    }
}
