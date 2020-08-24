<?php

namespace App\Services\ContentApi\TagGroups;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Country extends TagGroup implements Arrayable, Jsonable
{
    const TAG_GROUP_ID = 1001;

    public function getCanonicalUrl()
    {
        return route('regions.show', ['region' => Str::slug($this->data['name'])]);
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        return [
            'tagId' => $this->data['tagId'],
            'name' => $this->data['name'],
        ];
    }

    public function getChildren()
    {
        // TODO
        $config = config('hosts.wtr');

        $countries = Arr::get($config, 'term-mapping.regions', []);

        $children = [];

        foreach ($countries[1] as $country => $parent) {
            if ($parent === $this->getName()) {
                $children[] = $country;
            }
        }

        return collect($children);
    }

    public function getChildrenIDs()
    {

        $tags = cacheGroupTags(Country::class);

        $tagIDs = [];

        if ($children = $this->getChildren()) {
            foreach ($children as $child) {
                $tagIDs[] = $tags->firstWhere('name', $child)['tagId'];
            }
        }

        return array_filter($tagIDs);
    }

    public function getParent()
    {
        // TODO
        $config = config('hosts.wtr');

        $countries = Arr::get($config, 'term-mapping.regions', []);

        $parent = null;

        foreach ($countries[1] as $country => $region) {
            if ($country === $this->getName()) {
                $parent = $region;
                break;
            }
        }
        return $parent;
    }
}
