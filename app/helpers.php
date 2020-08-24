<?php

use App\Models\Brand;
use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

function is_path_active(string $givenPath)
{
    $request = request();
    $activePath = '/' . $request->path();

    // If the current path matches, we're good.
    if (strpos($activePath, $givenPath) === 0) {
        return true;
    }

    if ($request->route()->isFallback) {
        if (Str::contains($request->get('menu_section'), $activePath)) {
            return true;
        }
    }

    return false;
}

function event_language_route($routeName, $params)
{

    if ($params['event']->language_code != 'en') {
        $params['language'] = $params['event']->language_code;

        return route('language.' . $routeName, $params);
    }

    return route($routeName, $params);
}

if (! function_exists('active_host')) {

    function active_host($desiredHost = null)
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $host = Brand::concludeActiveHost(request()->getHost());

        if ($desiredHost) {
            return $desiredHost === $host;
        }

        return $host;
    }
}

if (! function_exists('get_host_config')) {

    /**
     * @return array|string|null
     */
    function get_host_config($key = null, $default = null)
    {
        $config = config('hosts.' . active_host());

        return ! empty($key) ? Arr::get($config, $key, $default) : $config;
    }
}

if (! function_exists('flattenRecursively')) {
    function flattenRecursively(array $array)
    {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }
}

if (! function_exists('getHostDefaultImage')) {

    /**
     * @param null $imageSize
     * @return string|null
     */
    function getHostDefaultImage($imageSize = null)
    {
        $defaultImage = get_host_config("default_images.$imageSize");

        return $defaultImage ? asset($defaultImage) : null;
    }
}

if (! function_exists('lang')) {

    /**
     * @param       $key
     * @param array $args
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function lang($key, $args = [])
    {
        return \App\Classes\Language::get($key, $args);
    }
}
if (! function_exists('root_for_brand')) {

    /**
     * @param Brand $brand
     * @param null  $secure
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function root_for_brand(Brand $brand, $secure = null)
    {
        $urlGenerator = app(UrlGenerator::class);

        $scheme = $urlGenerator->formatScheme($secure);
        $baseHost = remove_url_protocol($brand->host);

        return $urlGenerator->formatRoot($scheme, $scheme . $baseHost);
    }
}

if (! function_exists('remove_url_protocol')) {
    /**
     * @param string $url
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function remove_url_protocol($url)
    {
        return $url = preg_replace("(^https?://)", "", $url);
    }
}

if (! function_exists('asset_from_brand')) {

    /**
     * @param Brand $brand
     * @param       $path
     * @param null  $secure
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function asset_from_brand(Brand $brand, $path, $secure = null)
    {
        $root = root_for_brand($brand, $secure);

        return app(UrlGenerator::class)->assetFrom($root, $path, $secure);
    }
}

if (! function_exists('url_brand')) {

    /**
     * @param Brand  $brand
     * @param string $url
     * @param null   $secure
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function url_brand(Brand $brand, $url = '', $secure = null)
    {
        $root = root_for_brand($brand, $secure);

        return app(UrlGenerator::class)->to($root . '/' . ltrim($url, '/'), $secure);
    }
}

if (! function_exists('get_country_iso_code')) {

    /**
     * Return two letters ISO Code with a country name as parameter
     *
     * @param string $countryName
     * @return string|null
     */
    function get_country_iso_code($countryName)
    {
        if (! $countryName) {
            return;
        }

        //unfortunately we need this for 'gb' iso code :)
        $countryName = ($countryName == 'United Kingdom') ? 'United Kingdom of Great Britain and Northern Ireland' : $countryName;
        $countryName = ($countryName == 'USA') ? 'United States of America' : $countryName;

        try {
            $isoCode = (new League\ISO3166\ISO3166())->name($countryName)['alpha2'] ?? false;

            if (! $isoCode) {
                return;
            }

            return strtolower($isoCode);
        } catch (\Exception $exception) {
            return null;
        }
    }
}

if (! function_exists('convert_encoding_body')) {
    function convert_encoding_body($expression)
    {
        return mb_convert_encoding($expression, 'HTML-ENTITIES', 'UTF-8');
    }
}

if (! function_exists('fix_html')) {
    function fix_html($expression)
    {
        $escapedHtml = convert_encoding_body($expression);

        return "<?php \$doc = new DOMDocument();@\$doc->loadHTML($escapedHtml);echo \$doc->saveHTML();  ?>";
    }
}

if (! function_exists('app_is_running_in_console_with_debug')) {
    /**
     * @return bool
     */
    function app_is_running_in_console_with_debug()
    {
        if (! App::runningInConsole()) {
            return false;
        }

        foreach ($_SERVER['argv'] as $commandArgument) {
            if ($commandArgument === '--debug=true') {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('generate_cache_key')) {
    function generate_cache_key(string $key, Brand $brand = null)
    {
        $host = $brand ? $brand->machine_name : active_host();
        $cacheKey = md5($host . $key);
        logger("cache_key '$cacheKey' made of:", compact('key', 'host'));

        return $cacheKey;
    }
}


if (! function_exists('cacheStuff')) {
    function cacheStuff(string $key, int $ttl, Closure $closure, array $tags = [], Brand $brand = null)
    {
        // $ttl from Laravel 5.8 onwards is in seconds
        if ($ttl === 0) {
            return $closure();
        }

        $ttl = $ttl * 60;

        // if in preview mode, don't cache content
        if (session('preview', false)) {
            return $closure();
        }

        $cacheKey = generate_cache_key($key, $brand);

        logger("Using cache key:", ['key' => $cacheKey, 'tags' => $tags]);

        if (count($tags) > 0) {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $closure);
        }

        return Cache::remember($cacheKey, $ttl, $closure);
    }
}

if (! function_exists('cacheGroupTags')) {
    function cacheGroupTags(string $key, Service $service = null): Collection
    {
        return cacheStuff($key . md5(serialize($service)), 10, function () use ($key, $service) {
            $tags = $key::getTags($service);

            return collect($tags)->transform(function (array $tag) {
                return [
                    'tagId' => $tag['tagId'],
                    'typeId' => $tag['typeId'],
                    'name' => $tag['name'],
                    'slug' => Str::slug($tag['name']),
                ];
            });
        }, ['tags']);
    }
}

if (! function_exists('brandService')) {
    function brandService(string $brand = null)
    {
        if (is_null($brand)) {
            $brand = active_host();
        }

        // Compatibility with admin
        $config = ['base_url' => '', 'preview' => '', 'secret' => ''];

        if ($brand !== 'admin') {
            $config = config("hosts.{$brand}.services.contentapi");
        }

        return new Service(
            $config['base_url'],
            previewMode() ? $config['preview'] : $config['secret'],
        );
    }
}

if (! function_exists('previewMode')) {
    function previewMode()
    {
        $editor = false;

        if ($loggedin = Auth::user()) {
            $editor = $loggedin->isEditor();
        }

        return session('preview', false) && $editor;
    }
}

if (! function_exists('latestMagazine')) {
    function latestMagazine(Service $service)
    {
        $search = $service->newSearch();
        $search->setTagIds([Magazine::ENTITY_TYPE_TAG_ID])
            ->setPageSize(1)
            ->setSort(Search::SORT_TYPE_LATEST);

        return $service->run($search)->hydrate()->first();
    }
}

if (! function_exists('hackyConfigService')) {
    function hackyConfigService()
    {
        $config = [
            'base_url' => env('CONTENTAPI_BASE_URL'),
            'secret' => 'HCMFCK5PYD2AWQC3NZBV38WCTKLQ58X8XUBA',
        ];

        return new Service(
            $config['base_url'],
            $config['secret'],
        );
    }
}

if (! function_exists('cookieProSource')) {
    function cookieProSource(): string
    {
        $cpConsent = get_host_config('services.cookiepro_consent');

        if (app()->environment('production')) {
            return "https://cookie-cdn.cookiepro.com/consent/{$cpConsent}.js";
        }

        if (app()->environment('local')) {
            return "https://cookiepro.blob.core.windows.net/consent/{$cpConsent}-test.js";
        }

        return "https://cookiepro.blob.core.windows.net/consent/{$cpConsent}-test.js";
    }
}

// these are needed for compatibility with inky
function str_plural($string, $num)
{
    return Illuminate\Support\Str::plural($string, $num);
}

function arr_get($array, $field, $default = null)
{
    return Illuminate\Support\Arr::get($array, $field, $default);
}
