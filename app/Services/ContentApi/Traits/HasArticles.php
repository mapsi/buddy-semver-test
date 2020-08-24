<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Search;

trait HasArticles
{
    protected $articles;

    public function fetchArticles($sort = Search::SORT_TYPE_LATEST)
    {
        if (! $this->articles) {
            $search = $this->service->newSearch();
            $search->setTagIds([Article::ENTITY_TYPE_TAG_ID]);
            $search->setRelationIds([$this->getId()]);
            $search->setSort($sort);

            $this->articles = $this->service->run($search)->hydrate();
        }

        return $this->articles;
    }
}
