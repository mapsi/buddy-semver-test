<?php

namespace App\Services\ContentApi\Entities\Profiles;

use App\Auth\Traits\ComparesPermissions;
use App\Models\Feature;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasRegions;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use App\Services\ContentApi\Interfaces\HasRegionsInterface;
use App\Services\ContentApi\Fields\ProfessionalNotice;
use App\Services\ContentApi\Traits\HasSections;

class OrganisationProfile extends Profile implements Arrayable, Jsonable, HasRegionsInterface
{
    use HasRegions;
    use HasFirms;
    use HasSections;
    use ComparesPermissions;
    use IsDirectoryProfile;

    const PROFILE_TYPE_TAG_ID = 17620;
    private $professionalNotice;

    public function __construct(array $data, Service $service = null)
    {
        parent::__construct($data, $service);
        $this->professionalNotice = new ProfessionalNotice($this->getInfo()['ProfessionalNotice']);
    }

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'url' => $this->getCanonicalUrl(),
            'mediaUrl' => $this->getMediaUrl(),
            'mediaUrlListing' => $this->getMediaUrl('thumb'),
            'country' => ($country = $this->getRegions()->first()) ? $country->toArray() : null,
            'hasProfessionalNotice' => $this->professionalNotice->hasProfessionalNotice()
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
            'publishedFrom' => null,
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $this->getMediaUrl('sm'),
            'editionName' => $editionName,
            'editionUrl' => $editionUrl,
            'type' => $this->getEntityType()
        ];
    }

    public function getProfessionalNotice(): ProfessionalNotice
    {
        return $this->professionalNotice;
    }

    public function hasProfessionalNotice(): bool
    {
        return $this->professionalNotice->hasProfessionalNotice();
    }

    public function requiresPermissions(): array
    {
        return [Feature::TYPE_GXR_100_CURRENT];
    }
}
