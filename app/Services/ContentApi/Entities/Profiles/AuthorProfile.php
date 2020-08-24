<?php

namespace App\Services\ContentApi\Entities\Profiles;

use App\Services\ContentApi\Entities\Author;
use App\Services\ContentApi\Entities\Profile;

class AuthorProfile extends Profile
{
    const PROFILE_TYPE_TAG_ID = 17619;

    public function getName()
    {
        return $this->data['title'];
    }

    public function getPosition()
    {
        return $this->getAuthor()->getPosition();
    }

    public function getAuthor()
    {
        return $this->getRelations(Author::class)->first();
    }

    public function getCompany()
    {
        return $this->getAuthor()->getCompany();
    }

    public function getBiography()
    {
        return $this->data['body'];
    }

    public function getEmail()
    {
        return $this->getAuthor()->getEmail();
    }

    public function getView()
    {
        return 'author.show';
    }

    public function getSearchableArray()
    {
        return [
            'id' => $this->getId(),
            'publishedFrom' => null,
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getAuthor()->getMediaUrl('sm'),
            'type' => $this->getEntityType()
        ];
    }
}
