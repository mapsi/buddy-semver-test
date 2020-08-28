<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Interfaces\Searchable;
use App\Services\ContentApi\TagGroups\DirectoryYear;

class DirectoryRanking extends DataEntity implements Searchable
{
    const ENTITY_TYPE_TAG_ID = 90736;

    public function __get($name)
    {
        if ($name === 'resource') {
            return $this;
        }
    }

    public function getView()
    {
        return null;
    }

    public function getHeadline()
    {
        return $this->data['title'];
    }

    public function getSearchableArray()
    {
        return [
            'id' => $this->getId(),
            'publishedFrom' => $this->getTagGroup(DirectoryYear::class)->first()->getName(),
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'precis' => null,
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getMediaUrl('sm'),
        ];
    }
}
