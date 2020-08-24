<?php

namespace App\Services\ContentApi\Entities\Profiles;

use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Traits\HasFirms;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class FirmProfile extends Profile implements Arrayable, Jsonable
{
    use HasFirms;

    const PROFILE_TYPE_TAG_ID = 69500;

    public function getName()
    {
        return $this->data['title'];
    }

    public function getView()
    {
        return 'authoring-organisation.show';
    }

    public function getSearchableArray()
    {
        return [
            'id' => $this->getId(),
            'publishedFrom' => null,
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getMediaUrl('sm'),
            'type' => $this->getEntityType()
        ];
    }

    public function getCanonicalUrl()
    {
        $url = $this->data['sourceLink'] ?? '';
        $pos = strpos($url, "://");
        $pos = false === $pos ? 0 : $pos + 3;

        return substr($url, strpos($url, '/', $pos));
    }
}
