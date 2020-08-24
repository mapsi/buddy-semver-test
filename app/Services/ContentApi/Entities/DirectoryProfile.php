<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Interfaces\Searchable;
use App\Services\ContentApi\TagGroups\DirectoryProduct;
use App\Services\ContentApi\TagGroups\ProfileType;
use Illuminate\Support\Arr;

class DirectoryProfile extends DataEntity implements Searchable
{
    const ENTITY_TYPE_TAG_ID = 92376;

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
        return $this->getInfo('FirmName');
    }

    public function getSearchableArray()
    {
        $product = $this->getTagGroup(DirectoryProduct::class)->first()->getName();
        $host = active_host();

        $body = Arr::get($this->getData(), 'precis');

        if ($firmName = $this->getInfo('FirmName')) {
            $body .= "
            <p>{$firmName}</p>
            ";
        }

        if ($area = $this->getRelations(DirectoryArea::class)->first()) {
            $body .= "
        <div class=\"n-span--9\">
            <p class=\"font--sm fg--{$host}-blue\"><span class=\"font--light\">Ranked in</span>
                <a href=\"{$area->getCanonicalUrl()}\">{$area->getTitle()}</a>
            </p>
        </div>
        ";
        }

        $tagGroup = $this->getTagGroup(ProfileType::class)->first();

        if ($tagGroup->getName() === 'Person' && $photo = $this->getMediaUrl('thumb')) {
            $body .= "
            <div class=\"n-span--3 n-span--last\">
                <div class=\"partner-image\">
                    <img src=\"{$photo}\">
                </div>
            </div>
            ";
        }

        return [
            'id' => $this->getId(),
            'publishedFrom' => null,
            'title' => $this->getTitle() . " ({$product})",
            'headline' => $this->getHeadline(),
            'precis' => $body,
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getMediaUrl('sm'),
        ];
    }
}
