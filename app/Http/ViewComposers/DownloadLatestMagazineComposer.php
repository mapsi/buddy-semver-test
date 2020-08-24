<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Search;

class DownloadLatestMagazineComposer
{
    public function compose(View $view)
    {
        $service = brandService();

        $magazine = latestMagazine($service);

        $view->with('latestMagazine', $magazine);
    }
}
