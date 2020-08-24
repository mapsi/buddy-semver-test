<?php

namespace App\Http\ViewComponents\ComponentLogic\ContentLinks;

use App\Http\ViewComponents\ComponentLogic\BaseComponentLogic;
use App\Http\ViewComponents\ComponentLogic\FetchesData;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Results;

class ContentLinkSurveyLogic extends BaseComponentLogic
{
    use FetchesData;

    /**
     * @return array
     */
    public function get()
    {
        $edition = $this->getEdition($this->attributes['survey_to_show']);
        if (! $edition) {
            \Log::warning('Edition not being shown on' . url()->current() . 'page, showing fallback');

            return $this->getEditionFallback();
        }

        $blockTitle = $this->attributes['title'] ?? null;
        $buttonText = $this->attributes['buttonText'] ?? lang('surveys.view_survey');

        return [
            'blockTitle' => $blockTitle,
            'blockLink' => $edition->getSlug(),
            'image' => $edition->getMediaUrl() ?: getHostDefaultImage('half-grid'),
            'homepagePromoImage' => $this->attributes['homepagePromoImage'] ?? '',
            'imageAlt' => $edition->getName(),
            'mainText' => $edition->getName(),
            'secondaryText' => $edition->getBody(),
            'link' => $edition->getCanonicalUrl(),
            'buttonText' => $buttonText,
        ];
    }

    /**
     * @return Edition|null
     */
    private function getEdition($path)
    {
        $editionPath = $path;
        $service = brandService();
        $edition = $service->findContentBySourceLink($editionPath);

        return $edition;
    }

    /**
     * @return array
     */
    private function getEditionFallback()
    {
        return [
            'blockTitle' => lang('surveys.surveys'),
            'blockLink' => '#',
            'homepagePromoImage' => '',
            'image' => '/images/misc/in-house-counsel-banner-800x275.jpg',
            'imageAlt' => lang('surveys.in_house_counsel'),
            'mainText' => lang('surveys.in_house_counsel'),
            'secondaryText' => lang('surveys.leading_lawyers'),
            'buttonText' => lang('surveys.view_survey'),
            'link' => '#',
        ];
    }
}
