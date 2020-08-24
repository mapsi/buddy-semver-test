<?php

namespace App\Providers;

use App\Classes\BrandSetter;
use App\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Support\Str;

class BrandServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(ViewFactory $view_factory, Brand $brand = null)
    {

        $activeHost = active_host();
        if (! $activeHost) {
            return null;
        }

        (new BrandSetter())->setHostBrand($activeHost)->loadConfiguration();

        $brandDirectories = self::brandsConfigPath()
            ->map(function ($path) {
                return $path . '/config';
            });

        // we need to load all configs as they are required by the admin side
        foreach ($brandDirectories as $brandDirectory) {
            foreach (File::allFiles($brandDirectory) as $file) {
                preg_match('/brands\/(\w+)\/config/', $brandDirectory, $outputArray);
                $brandString = $outputArray[1];

                if ($file->getExtension() === 'php') {
                    $relativePath = $file->getRelativePathname();
                    $key = 'hosts.' . $brandString . '.' . str_replace('/', '.', str_replace(['.php'], '', $relativePath));
                    if (Str::contains($key, 'settings')) {
                        // add the settings file in the config "root"
                        // we need to get the existing values and merge them with the root values
                        $key = str_replace('.settings', '', $key);
                        $original = config($key, []);

                        config([str_replace('.settings', '', $key) => $this->mergeConfig(require $file->getRealPath(), $original)]);

                        continue;
                    }

                    config([$key => require $file->getRealPath()]);
                }
            }
        }

        if ($brand) {
            $mn = $brand->machine_name;
            $whiteLabelBrands = ['gdr', 'gbrr', 'grr', 'gcr', 'gir', 'gar'];

            if (in_array($mn, $whiteLabelBrands)) {
                $view_factory
                    ->getFinder()
                    ->prependLocation(base_path("brands/_shared/resources/views"));
            }

            // Set any brand-specific views.
            $view_factory
                ->getFinder()
                ->prependLocation(base_path("brands/{$mn}/resources/views"));

            /** @var FileLoader $loader */
            $loader = app('translation.loader');
            $loader->addNamespace($mn, base_path("brands/{$mn}/resources/lang"));

            return;
        }

        $view_factory->getFinder()->prependLocation(resource_path('views/admin'));
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Brand::class, function () {
            if ($this->app->runningInConsole()) {
                return null;
            }
            $activeHost = active_host();
            if (! $activeHost) {
                return null;
            }

            return $this->isValidBrandHost($activeHost)
                ? Brand::findByMachineName($activeHost)
                : null;
        });
    }

    /**
     * @param string $hostname
     * @return bool
     */
    private function isValidBrandHost(string $hostname)
    {
        return ! $this->isAdminHost($hostname) && $this->anyBrandExists();
    }

    /**
     * @param string $hostname
     * @return boolean
     */
    private function isAdminHost(string $hostname)
    {
        return Str::contains($hostname, 'admin');
    }

    /**
     * @return bool
     */
    private function anyBrandExists()
    {
        return Schema::hasTable('brands') && Brand::exists();
    }

    protected function mergeConfig(array $original, array $merging)
    {
        $array = array_merge($original, $merging);

        foreach ($original as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (! Arr::exists($merging, $key)) {
                continue;
            }

            if (is_numeric($key)) {
                continue;
            }

            $array[$key] = $this->mergeConfig($value, $merging[$key]);
        }

        return $array;
    }

    public static function brandsConfigPath(): Collection
    {
        $baseBrandDirectory = base_path("brands");

        return collect(File::directories($baseBrandDirectory))
            ->filter(function ($path) {
                return ! Str::contains($path, ['_shared']);
            })
            ->values();
    }
}
