<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\Entities\Author;
use App\Services\ContentApi\Entities\DataEntity;
use App\Services\ContentApi\TagGroups\Brand;
use Illuminate\Support\Arr;

trait HasAuthors
{
    public function getAuthors()
    {
        $authors = $this->getRelations(Author::class);

        if (
            Arr::first($this->data['tags'], function ($tag) {
                return $tag['tagId'] == Brand::TAG_ID_KNOWHOWGCR;
            })
        ) {
            $authorsIds = $authors->map(function ($author) {
                return $author->getId();
            })->values()->toArray();
            $results = $this->service->getBulkByIds($authorsIds);

            $results = collect($results->getData());

            $authors = $results->map(function ($result) {
                return new Author($result, null);
            });

            $authors = $authors->filter(function (Author $item) {
                foreach ($item->getTags() as $x) {
                    if ($x['tagId'] === DataEntity::TAG_ID_SOURCED_FROM_DRUPAL) {
                        return true;
                    }
                }
            });

            return $authors;
        }

        if ($this->getTagGroup(Brand::class)->first()) {
            $authors->transform(function ($author) {
                $brand = $this->getTagGroup(Brand::class)->first()->getSlug();
                return brandService($brand)->getContent($author->getId());
            });
        }

        return $authors->filter(function (Author $item) {

            if ($this->isLexology()) {
                // Only show Lex authors if a Lex article
                foreach ($item->getTags() as $x) {
                    if ($x['tagId'] === Brand::TAG_ID_LEXOLOGY) {
                        return true;
                    }
                }
            } else {
                // Only show Drupal authors
                foreach ($item->getTags() as $x) {
                    if ($x['tagId'] === DataEntity::TAG_ID_SOURCED_FROM_DRUPAL) {
                        return true;
                    }
                }
            }
        });
    }

    public function getAuthorIDs()
    {
        return $this->getRelations(Author::class)->map->getId();
    }

    public function getAuthorsBaseInfo()
    {
        $authors = $this->getRelations(Author::class);
        $result = [];
        foreach ($authors as $author) {
            $isBrandAuthor = $author->getTagGroup(Brand::class)->first(function ($brand) {
                return $brand->getTagId() == get_host_config('tag_id');
            });

            if ($isBrandAuthor) {
                $result[] = [
                    'id' => $author->getId(),
                    'name' => $author->getName(),
                ];
            }
        }
        return $result;
    }
}
