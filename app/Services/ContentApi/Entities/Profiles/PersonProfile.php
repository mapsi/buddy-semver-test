<?php

namespace App\Services\ContentApi\Entities\Profiles;

use App\Auth\Traits\ComparesPermissions;
use App\Models\Feature;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasRegions;
use App\Services\ContentApi\Traits\HasSections;
use App\Services\ContentApi\Interfaces\HasRegionsInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;

class PersonProfile extends Profile implements Arrayable, Jsonable, HasRegionsInterface
{
    use HasRegions;
    use HasFirms;
    use HasSections;
    use ComparesPermissions;
    use IsDirectoryProfile;

    const PROFILE_TYPE_TAG_ID = 17621;

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'url' => $this->getCanonicalUrl(),
            'mediaUrlListing' => $this->getMediaUrl('thumb'),
            'mediaUrlProfile' => $this->getMediaUrl('avatar'),
            'country' => ($country = $this->getRegions()->first()) ? $country->toArray() : null,
            'firm' => ($firm = $this->getFirms()->first()) ? $firm->toArray() : null,
        ];
    }

    public function getName()
    {
        return $this->data['title'];
    }

    public function getEdition()
    {
        return $this->getRelations(Edition::class)->first();
    }

    public function getView()
    {
        return null;
    }

    public function getSearchableArray()
    {
        // check if part of content collection
        $editionName = null;
        $editionUrl = null;

        if ($this->getSection()) {
            $edition = $this->getSection()->getEdition();
            if ($edition) {
                $editionName = $edition->getName();
                $editionUrl = $edition->getCanonicalUrl();
            }
        }

        return [
            'id' => $this->getId(),
            'publishedFrom' => $this->getPublicationDate(),
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getMediaUrl('sm'),
            'editionName' => $editionName,
            'editionUrl' => $editionUrl,
            'type' => $this->getEntityType()
        ];
    }

    public function requiresPermissions(): array
    {
        return [Feature::TYPE_GXR_100_CURRENT];
    }
}
