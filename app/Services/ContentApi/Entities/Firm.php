<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Entities\Profiles\FirmProfile;
use App\Services\ContentApi\Interfaces\HasProfile as HasProfileInterface;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\FirmContributorType;
use App\Services\ContentApi\TagGroups\IndustryJurisdiction;
use App\Services\ContentApi\Traits\HasProfile;

class Firm extends DataEntity implements HasProfileInterface
{
    use HasProfile;

    const ENTITY_TYPE_TAG_ID = 2824;

    public function toArray()
    {
        return [
            'name' => $this->getName(),
        ];
    }

    public function getName()
    {
        return $this->data['title'];
    }

    public function getView()
    {
        return 'editions.edition_country_firm.blade';
    }

    public function getProfileTypeTagId()
    {
        return FirmProfile::PROFILE_TYPE_TAG_ID;
    }

    public function getWebsite()
    {
        return $this->getInfo('Website');
    }

    public function getCountry()
    {
        return $this->getInfo('Country');
    }

    public function getIndustryJurisdiction()
    {
        return $this->getTagGroup(IndustryJurisdiction::class)->first();
    }

    public function getPhone()
    {
        return $this->getInfo('Telephone');
    }

    public function getFax()
    {
        return $this->getInfo('Fax');
    }

    public function getAddress1()
    {
        return $this->getInfo('Address1');
    }

    public function getAddress2()
    {
        return $this->getInfo('Address2');
    }

    public function getAddress3()
    {
        return $this->getInfo('Address3');
    }

    public function getContacts()
    {
        return $this->getInfo('Contacts');
    }

    public function getContributorTypes()
    {
        return $this->getTagGroup(FirmContributorType::class);
    }

    public static function fetchContributors($brand, $type)
    {
        $service = hackyConfigService();

        $contributorTypeTagId = FirmContributorType::TAG_ID_IAM_CONTRIBUTOR;

        if ($brand === 'wtr') {
            $contributorTypeTagId = FirmContributorType::TAG_ID_WTR_CONTRIBUTOR;
            if ($type === 'daily') {
                $contributorTypeTagId = FirmContributorType::TAG_ID_WTR_DAILY_CONTRIBUTOR;
            }
        }

        $jurisdictions = cacheGroupTags(IndustryJurisdiction::class, $service);
        $tagIds = $jurisdictions->map(function ($ij) use ($contributorTypeTagId) {
            return [$ij['tagId'], $contributorTypeTagId];
        });

        $search = $service->newSearch();
        $search
            ->setTagIds($tagIds->all())
            ->setPageSize(40);

        return $service->run($search)->hydrate();
    }
}
