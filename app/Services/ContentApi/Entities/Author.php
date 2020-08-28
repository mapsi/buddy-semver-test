<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Entities\Profiles\AuthorProfile;
use App\Services\ContentApi\Interfaces\HasProfile as HasProfileInterface;
use App\Services\ContentApi\TagGroups\AuthorType;
use App\Services\ContentApi\Traits\HasArticles;
use App\Services\ContentApi\Traits\HasProfile;
use Illuminate\Support\Str;

class Author extends DataEntity implements HasProfileInterface
{
    use HasArticles;
    use HasProfile;

    const ENTITY_TYPE_TAG_ID = 2823;

    public function getBiography()
    {
        return $this->getBody();
    }

    public function getName()
    {
        return $this->data['title'];
    }

    public function getPosition()
    {
        return $this->getInfo('JobTitle');
    }

    public function getCompany()
    {
        return $this->getInfo('Company');
    }

    public function getLocation()
    {
        return $this->getInfo('Location');
    }

    public function getEmail()
    {
        return $this->getInfo('Email');
    }

    public function getBrands()
    {
        return $this->data['tags'];
    }

    public function getTags()
    {
        return $this->data['tags'];
    }

    public function getHeadline()
    {
        return $this->data['title'];
    }

    public function getView()
    {
        return null;
    }

    public function getProfileTypeTagId()
    {
        return AuthorProfile::PROFILE_TYPE_TAG_ID;
    }
    public function getType()
    {
        return Str::slug(($authorType = $this->getTagGroup(AuthorType::class)->first()) ? $authorType->getName() : null);
    }

    public function getUrlPath()
    {
        return $this->getInfo('UrlPath');
    }

    public function getMediaUrl(string $variant = null)
    {
        //attempt to resolve path if legacy image passed
        $authorimages = include 'DataFiles/authorimages.php';
        $authorguid = $this->getOriginalId();

        if (array_key_exists($authorguid, $authorimages)) {
            // check if image exists then serve it if it does
            $imagePath = parent::getMediaUrl($variant);
            if (empty($imagePath)) {
                return '';
            }
            $headers = get_headers($imagePath);
            $imageExists = stripos($headers[0], "200 OK") ? true : false;

            if ($imageExists) {
                return parent::getMediaUrl($variant);
            }

            // try serving legacy image from the mapping file
            $path = $authorimages[$authorguid];
            $headers = get_headers($path);
            $exists = stripos($headers[0], "200 OK") ? true : false;
            if ($exists && ! $variant) {
                return $path;
            }
        }

        return parent::getMediaUrl($variant);
    }
}
