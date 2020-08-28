<?php

namespace App\Services;

use App\Models\Event\Event;
use App\Newsletter\INewsletter;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\TagGroups\SubBrand;
use App\Services\ContentApi\TagGroups\SubBrandSection;

class EmailService
{
    protected $email;
    private $brandMachineName;

    protected $gdrHeadlines = [];

    public function getData(string $brandMachineName, string $type, $email = null): array
    {
        $this->email = $email;
        $this->brandMachineName = $brandMachineName;
        /** @var INewsletter $newsletterService */
        $newsletterService = resolve(config("newsletter.brand_provider.{$brandMachineName}"));

        return array_merge(
            $this->{"get{$this->brandMachineName}{$type}Data"}(),
            [
                'config' => config("emails.content.{$this->brandMachineName}_{$type}", []),
                'newsletterServiceName' => $newsletterService::getFriendlyName(),
            ]
        );
    }

    private function getWtrDailyData(): array
    {
        return [
            'legalUpdates' => $this->getLegalUpdates(),
            'thoughtLeadership' => $this->getReports([ArticleType::TAG_ID_THOUGHT_LEADERSHIP]),
            'newsAndUpdates' => $this->getReports([ArticleType::TAG_ID_BLOG]),
        ];
    }

    protected function getLegalUpdates()
    {
        // return Article::mostRecent()
        //     ->select(['id', 'title', 'email_date'])
        //     ->ofBrand($this->brandMachineName)
        //     ->ofType('legal update')
        //     ->whereEmailDateIsBetween(7, $this->email->scheduled_for ?? null)
        //     ->take(40)
        //     ->get();

        //TODO query by "field_email_date"

        $service = brandService($this->brandMachineName);
        $search = $service->newSearch();

        $search
            ->setTagIds([ArticleType::TAG_ID_LEGAL_UPDATE])
            ->setPageSize(40)
            ->setSort(Search::SORT_TYPE_LATEST)
            ->withContent();

        $result = $service->run($search, 1)->hydrate();

        $legalUpdates = [];

        foreach ($result as $item) {
            $legalUpdates[] = [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'is_from_today' => $item->getPublicationDate(false)->isToday(),
            ];
        }

        return $legalUpdates;
    }

    protected function getReports($types, $excludedTypes = [])
    {
        $service = brandService($this->brandMachineName);
        $search = $service->newSearch();

        $search
            ->setTagIds($types)
            ->excludeTagIds($excludedTypes)
            ->setPageSize(40)
            ->setSort(Search::SORT_TYPE_LATEST)
            ->withContent();

        $result = $service->run($search, 1)->hydrate();

        $reports = [];

        foreach ($result as $item) {
            $reports[$item->getId()] = $item->getTitle();
        }

        return $reports;
    }

    private function getWtrWeeklyData(): array
    {
        return [
            'legalUpdates' => $this->getLegalUpdates(),
            'internationalReports' => $this->getReports([
                [ArticleType::TAG_ID_INTERNATIONAL_REPORT],
                [ArticleType::TAG_ID_INDUSTRY_REPORT],
            ]),
            'contributors' => collect(),
            'thoughtLeadership' => $this->getReports([ArticleType::TAG_ID_THOUGHT_LEADERSHIP]),
            'events' => $this->getEvents(),
        ];
    }

    protected function getEvents()
    {
        return Event::query()
            ->brand($this->brandMachineName)
            ->pluck('title', 'id');
    }

    private function getIamWeeklyData(): array
    {
        return [
            'industryReports' => $this->getReports([ArticleType::TAG_ID_INDUSTRY_REPORT]),
            'internationalReports' => $this->getReports([ArticleType::TAG_ID_INTERNATIONAL_REPORT]),
            'contributors' => collect(),
            'events' => $this->getEvents(),
        ];
    }

    private function getGdrDailyData()
    {
        return [
            'articles' =>  $this->getReports([ArticleType::TAG_ID_NEWS]),
        ];
    }

    private function getGbrrDailyData()
    {
        return array_merge($this->getGbrrFocusInsightData(), [
            'articles' =>  $this->getReports([ArticleType::TAG_ID_NEWS]),
        ]);
    }

    private function getGrrDailyData()
    {
        return [
            'articles' =>  $this->getReports([ArticleType::TAG_ID_NEWS]),
            'features' =>  $this->getReports([ArticleType::TAG_ID_FEATURES]),
        ];
    }

    private function getGcrDailyData()
    {
        return [
            'articles' =>  $this->getReports([ArticleType::TAG_ID_NEWS]),
            'features' =>  $this->getReports([ArticleType::TAG_ID_NEWS]),
            'analysis' =>  $this->getReports([ArticleType::TAG_ID_ANALYSIS]),
        ];
    }

    private function getGbrrFocusInsightData()
    {
        return [
            'focusInsights' =>  $this->getReports([ArticleType::TAG_ID_FOCUS_INSIGHT]),
        ];
    }

    private function getGcrUsaData()
    {
        return [
            'articles' =>  $this->getReports([ArticleType::TAG_ID_ARTICLE, SubBrand::TAG_ID_GCR_USA], [SubBrandSection::TAG_ID_GCR_USA_TIPLINE]),
            'features' =>  $this->getReports([ArticleType::TAG_ID_ARTICLE, SubBrand::TAG_ID_GCR_USA], [SubBrandSection::TAG_ID_GCR_USA_TIPLINE]),
            'tiplines' =>  $this->getReports([SubBrandSection::TAG_ID_GCR_USA_TIPLINE]),
        ];
    }
}
