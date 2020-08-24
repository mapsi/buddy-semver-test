<?php

namespace App\Services\ContentApi\Traits;

use App\Services\ContentApi\Entities\Profile;

trait HasProfile
{
    protected $profile;

    public function fetchProfile()
    {
        if (! $this->profile) {
            $tagIds = [
                Profile::ENTITY_TYPE_TAG_ID,
                $this->getProfileTypeTagId(),
            ];

            $search = $this->service->newSearch();
            $search->setTagIds($tagIds);
            $search->setRelationIds([$this->getId()]);

            $this->profile = $this->service->run($search)->hydrate()->first();
        }

        return $this->profile;
    }
}
