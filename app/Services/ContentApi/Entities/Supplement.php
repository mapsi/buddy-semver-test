<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\TagGroups\SupplementIssue;
use Illuminate\Support\Str;

class Supplement extends IsMagazine
{
    const ENTITY_TYPE_TAG_ID = 5555;

    const MAX_LISTING_PRECIS_CHARACTER_LENGTH = 400;

    public function contributorsUrl()
    {
        if (false) {
            return $this->getCanonicalUrl() . '/contributors';
        }

        return null;
    }

    public function isCopublished()
    {
        echo __METHOD__;
        return null;
    }

    public function getView()
    {
        return 'magazines.supplement';
    }

    public function getSummary()
    {
        return $this->getInfo('Summary');
    }

    public function getListingSummary()
    {
        if ($this->getSummary()) {
            return $this->getSummary();
        }

        $firstParagraphPrecisText = Str::before($this->data['precis'], "\n");
        if (strlen($firstParagraphPrecisText) <= self::MAX_LISTING_PRECIS_CHARACTER_LENGTH) {
            return $firstParagraphPrecisText;
        }

        $reducedPrecisText = substr($this->data['precis'], 0, self::MAX_LISTING_PRECIS_CHARACTER_LENGTH);

        return Str::words($this->data['precis'], str_word_count($reducedPrecisText));
    }
}
