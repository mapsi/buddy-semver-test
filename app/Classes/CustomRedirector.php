<?php

namespace App\Classes;

use App\Models\Brand;
use App\Models\RedirectV2;
use Symfony\Component\HttpFoundation\Request;
use Spatie\MissingPageRedirector\Redirector\Redirector;

class CustomRedirector implements Redirector
{
    public function getRedirectsFor(Request $request): array
    {
        $brand = Resolve(Brand::class);
        $path = urldecode('/' . $request->path());

        if (! isset($brand)) {
            return [];
        }

        // cache each redirect separatelly for a week
        return cacheStuff(__METHOD__ . $brand->id . $path, 10080, function () use ($brand, $path) {
            return RedirectV2::where('brand_id', $brand->id)
                ->where('old', $path)
                ->get()
                ->flatMap(function ($redirect) {
                    return [$redirect->old => [$redirect->new, $redirect->code]];
                })->toArray();
        });
    }
}
