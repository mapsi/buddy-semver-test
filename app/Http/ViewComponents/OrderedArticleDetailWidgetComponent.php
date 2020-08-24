<?php

namespace App\Http\ViewComponents;

use App\Services\ContentApi\Service;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

/**
 * Article detail page most read widget component
 *
 * Class OrderedArticleDetailWidgetComponent
 * @package App\Http\ViewComponents
 */
class OrderedArticleDetailWidgetComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return $this->attributes['template'] ?? 'blocks.ordered_article_detail_widget';
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
