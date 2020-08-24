<?php

namespace App\Http\ViewComponents;

use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;
use App\Services\ContentApi\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class PagedTiplineListComponent extends PageBlockBase
{
    public function view()
    {
        return 'blocks.paged_tipline_list';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'articles' => $this->loadData()->paginate(),
            'attributes' => $this->attributes,
            'mostRead' => Service::fetchMostRead()
        ];
    }
}
