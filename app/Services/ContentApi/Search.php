<?php

namespace App\Services\ContentApi;

use Carbon\Carbon;

class Search
{
    const ITEMS_PER_PAGE = 100;

    const SORT_TYPE_LATEST = 1;
    const SORT_TYPE_RELEVANCE = 2;
    const SORT_TYPE_TITLE = 3;
    const SORT_TYPE_READS_TOTAL_DESC = 4;
    const SORT_TYPE_READS_TOTAL_DAY_DESC = 5;
    const SORT_TYPE_READS_TOTAL_WEEK_DESC = 6;
    const SORT_TYPE_SHARES_WEEK_DESC = 7;
    const SORT_TYPE_EARLIEST = 8;
    const SORT_TYPE_LATEST_UPDATED = 9;
    const SORT_TYPE_CONTENT_DATE_LATEST = 10;
    const SORT_TYPE_CONTENT_DATE_EARLIEST = 11;
    const SORT_TYPE_READS_TOTAL_14_DAYS_DESC = 13;
    const SORT_TYPE_READS_TOTAL_30_DAYS_DESC = 14;
    const SORT_TYPE_READS_TOTAL_90_DAYS_DESC = 15;

    const TAG_GROUP_ID_BODY_FORMAT = 5;
    const TAG_GROUP_ID_AUTHOR_FLAG = 500;

    const TAG_GROUP_NAME = [
        TagGroups\Author::TAG_GROUP_ID => 'Author',
        TagGroups\ArticleType::TAG_GROUP_ID => 'Article type',
        TagGroups\Country::TAG_GROUP_ID => 'Region',
        TagGroups\Sector::TAG_GROUP_ID => 'Sector',
        TagGroups\Topic::TAG_GROUP_ID => 'Topic',
        TagGroups\MagazineSection::TAG_GROUP_ID => 'Magazine section',
        TagGroups\DirectoryProduct::TAG_GROUP_ID => 'Directory product',
        TagGroups\DirectoryProfileType::TAG_GROUP_ID => 'Directory profile type',
        TagGroups\DirectoryYear::TAG_GROUP_ID => 'Directory year',
        TagGroups\WorkArea::TAG_GROUP_ID => 'Work area',
    ];

    protected $brand;

    protected $params = [
        'tagIds' => [],
        'relations' => [],
        'orRelations' => [],
        'contentRelations' => [],
        'excludeTagIds' => [],
        'titleKeywords' => '',
        'keywords' => '',
        'pageSize' => self::ITEMS_PER_PAGE,
        'page' => 1,
        'sourceLink' => '',
        'slug' => '',
        'withContent' => false,
        'withFacets' => false,
        'withHighlights' => false,
        'sort' => self::SORT_TYPE_LATEST,
        'pruneFacets' => true,
        'forceUncached' => false,
        'tagSource' => 2,
        'fromDate' => null,
        'toDate' => null,
    ];

    public function __construct()
    {
        $this->params['toDate'] = date('c');
    }

    public function setTagIds(array $tagIds)
    {
        $setTagIds = [$tagIds];

        if (is_array($tagIds) && count($tagIds) > 0 && is_array(current($tagIds))) {
            $setTagIds = $tagIds;
        }
        $this->params['tagIds'] = $setTagIds;

        return $this;
    }

    /**
     * Retrieve contents related to one id
     * @param array $relationIds array containing entity id - only one id can be specified
     */
    public function setRelationIds(array $relationIds)
    {
        $this->params['relations'] = $relationIds;

        return $this;
    }

    /**
     * Retrieve contents related to different ids
     * @param array $relationIds array containing entity ids
     */
    public function setOrRelations(array $relationIds)
    {
        $this->params['orRelations'] = $relationIds;

        return $this;
    }

    public function setSourceLink(string $sourceLink)
    {
        $this->params['sourceLink'] = $sourceLink;

        return $this;
    }

    public function setSlug(string $slug)
    {
        $this->params['slug'] = $slug;

        return $this;
    }

    public function setPageSize(int $pageSize)
    {
        $this->params['pageSize'] = $pageSize;

        return $this;
    }

    public function getPageSize()
    {
        return $this->params['pageSize'];
    }

    public function setStartPage(int $startPage)
    {
        $this->params['page'] = $startPage;

        return $this;
    }

    public function getStartPage()
    {
        return $this->params['page'];
    }

    public function setQuery(string $query)
    {
        $this->params['keywords'] = $query;

        return $this;
    }

    public function excludeTagIds(array $tagIds)
    {
        $this->params['excludeTagIds'] = $tagIds;

        return $this;
    }

    public function setTitle(string $query)
    {
        $this->params['titleKeywords'] = $query;

        return $this;
    }

    public function withContent()
    {
        $this->params['withContent'] = true;

        return $this;
    }

    public function withFacets()
    {
        $this->params['withFacets'] = true;

        return $this;
    }

    public function withoutFacets()
    {
        $this->params['withFacets'] = false;

        return $this;
    }

    public function withHighlights()
    {
        $this->params['withHighlights'] = true;

        return $this;
    }

    public function pruneFacets()
    {
        $this->params['pruneFacets'] = true;

        return $this;
    }

    public function dontPruneFacets()
    {
        $this->params['pruneFacets'] = false;

        return $this;
    }

    public function setSort(int $sort)
    {
        $this->params['sort'] = $sort;

        return $this;
    }

    public function setFromDate(Carbon $date)
    {
        $this->params['fromDate'] = $date->toISOString();

        return $this;
    }

    public function setSearchBoosts(array $boostParams)
    {
        $this->params = array_merge($this->params, $boostParams);
    }

    public function format()
    {
        $params = array_filter($this->params);

        $params['forceUncached'] = (previewMode() ? true : $this->params['forceUncached']);
        $params['withContent'] = $this->params['withContent'];
        $params['withFacets'] = $this->params['withFacets'];
        $params['withHighlights'] = $this->params['withHighlights'];
        $params['pruneFacets'] = $this->params['pruneFacets'];
        $params['titleKeywords'] = strip_tags($this->params['titleKeywords']);

        if (previewMode()) {
            if (isset($params['toDate'])) {
                unset($params['toDate']);
            }
        }

        return [
            'json' => $params,
        ];
    }
}
