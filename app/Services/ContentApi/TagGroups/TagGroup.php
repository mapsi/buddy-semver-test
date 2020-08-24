<?php

namespace App\Services\ContentApi\TagGroups;

use App\Services\ContentApi\Service;
use Illuminate\Support\Str;

abstract class TagGroup
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function getTags(Service $service = null)
    {
        $service = $service ?? brandService();

        return collect(array_map([static::class, 'mapTag'], $service->getTagGroupTags(static::TAG_GROUP_ID)));
    }

    protected static function mapTag($tag)
    {
        return [
            'tagId' => $tag['tagId'],
            'typeId' => $tag['typeId'],
            'name' => $tag['name'],
            'slug' => Str::slug($tag['name']),
        ];
    }

    public function getTagId()
    {
        return $this->data['tagId'];
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function getSlug()
    {
        return Str::slug($this->data['name']);
    }

    public function getCanonicalUrl()
    {
        return url('/' . $this->getSlug());
    }
}
