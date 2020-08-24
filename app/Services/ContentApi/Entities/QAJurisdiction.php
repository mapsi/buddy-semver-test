<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\TagGroups\LexologyJurisdiction;
use App\Services\ContentApi\Entities\Contributor;
use App\Services\ContentApi\Entities\Author;
use App\Services\ContentApi\Entities\QATopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasFirmProfiles;

class QAJurisdiction extends DataEntity
{
    use HasFirms;
    use HasFirmProfiles;

    const ENTITY_TYPE_TAG_ID = 16709;
    public function getCountry(): string
    {
        $jurisdiction = $this->getTagGroup(LexologyJurisdiction::class)->first();
        return (! empty($jurisdiction)) ? $jurisdiction->getName() : "";
    }

    public function getAuthors(): Collection
    {
        return $this->getRelations(Author::class) ?? collect();
    }

    public function getContributor(): Collection
    {
        return $this->getRelations(Contributor::class) ?? collect();
    }

    public function getTopic(): Collection
    {
        return $this->getRelations(QATopic::class) ?? collect();
    }

    public function getCanonicalUrl()
    {
        $topic = $this->getTopic()->first();
        $url = "";
        if ($topic) {
            $url = route('know-how.show-jurisdiction-report', ['topic' => $topic->getSlug(), 'country' => Str::slug($this->getCountry())]);
        }
        return $url;
    }

    public function getView()
    {
        return '';
    }

    public function getContentDate($format = null)
    {
        return $this->formatConditionally('contentDate', $format);
    }
}
