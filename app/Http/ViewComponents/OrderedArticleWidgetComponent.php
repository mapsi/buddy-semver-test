<?php

namespace App\Http\ViewComponents;

use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class OrderedArticleWidgetComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return $this->attributes['template'] ?? 'blocks.ordered_article_widget';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        if (isset($this->attributes['most_read_extra_tag'])) {
            $mostReadTag = $this->attributes['most_read_extra_tag'];
        } else {
            $mostReadTag = null;
        }

        return [
            'articles' => Service::fetchMostRead($mostReadTag),
            'attributes' => $this->attributes,
        ];
    }
}
