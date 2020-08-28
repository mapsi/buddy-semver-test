<?php

namespace App\Services\ContentApi;

use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\TagGroups\MagazineSection;
use Illuminate\Support\Arr;

class Section
{
    protected $tagId;
    protected $items;

    public function __construct(int $tagId, array $items = [])
    {
        $this->tagId = $tagId;
        $this->items = collect($items);
    }

    public function add(Article $article)
    {
        $this->items->push($article);

        return $this;
    }

    public function getId()
    {
        return $this->tagId;
    }

    public function getTitle()
    {
        return Arr::get(MagazineSection::$title, $this->tagId);
    }

    public function getArticles()
    {
        return $this->items;
    }

    public function isCopublished()
    {
        if ($this->getTitle() === 'Roundtable' || $this->getTitle() === 'Country correspondent') {
            return true;
        } else {
            return false;
        }
    }
}
