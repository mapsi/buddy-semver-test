<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\Entities\Firm;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

trait HasFirms
{
    public function getFirms(): Collection
    {
        $entities = $this->getRelations(Firm::class);



        if (
            Arr::first($this->data['tags'], function ($tag) {
                return $tag['tagId'] == Brand::TAG_ID_KNOWHOWGCR;
            })
        ) {
            return $entities;
        }

        return $entities->filter(function (Firm $item) {

            if ($this->isLexology()) {
                // Only show Lex firms if a Lex article
                foreach ($item->data['tags'] as $x) {
                    if ($x['tagId'] === Brand::TAG_ID_LEXOLOGY) {
                        return true;
                    }
                }
            } else {
                // TODO - make tag-based
                return $item->getSourceId() === Service::SOURCE_ID_FIRM;
            }
        });
    }
}
