<?php

namespace App\Classes;

use App\Models\Brand;
use App\Models\Email;
use App\Models\Event\Event;
use App\Services\ContentApi\Entities\Firm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EmailPresenter
{
    /**
     * @var
     */
    private $brand;

    /**
     * @var
     */
    private $config;

    /**
     * @var
     */
    private $data;

    /** @var Email */
    protected $previousEmail;

    /**
     * @param array $data
     * @return array
     */
    public function build(array $data)
    {
        $this->setData($data);

        return array_merge(
            $this->buildBannersData(),
            $this->buildCommonData()
        );
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->setConfig();
        $this->setBrand();
    }

    /**
     * @return void
     */
    private function setBrand()
    {
        $this->brand = Brand::where('machine_name', $this->data['brand'])->firstOrFail();
    }

    /**
     * @return void
     */
    private function setConfig()
    {
        $this->config = config("emails.content.{$this->data['brand']}_{$this->data['type']}");
    }

    /**
     * @return array
     */
    public function buildBannersData()
    {
        $bannersData = [];
        foreach ($this->config['blocks'] as $blockName) {
            if (! Str::contains($blockName, 'banner')) {
                continue;
            }
            $method = 'build' . Str::studly($blockName) . 'Data';
            $bannersData[$blockName] = $this->$method();
        }

        return $bannersData;
    }

    private function buildWysiwygTextData()
    {
        return [
            'enabled' => Arr::get($this->data, 'wysiwyg_text.enabled', false),
            'title' => $this->data['wysiwyg_text']['title'] ?? null,
            'value' => $this->data['wysiwyg_text']['value'] ?? null,
        ];
    }

    /**
     * @return array
     */
    private function buildTopBannerData()
    {
        $topBannerFilepath = $this->data['top_banner']['filepath']
            ?? $this->loadFromLastEmail('top_banner.filepath');

        $topBannerEnabled = Arr::get($this->data, 'top_banner.enabled', false)
            ?? $this->loadFromLastEmail('top_banner.enabled', true);

        $topBannerUrl = $this->data['top_banner']['url']
            ?? $this->loadFromLastEmail('top_banner.url');

        return [
            'enabled' => $topBannerEnabled,
            'filepath' => $topBannerFilepath,
            'fileurl' => $this->getUrl($topBannerFilepath),
            'url' => $topBannerUrl,
        ];
    }

    /**
     * @param      $contentAttribute
     * @param null $default
     * @return null|string
     */
    private function loadFromLastEmail($contentAttribute, $default = null)
    {
        if (! $this->previousEmail) {
            $this->previousEmail = Email::where('brand_id', $this->brand->id)->where('type', $this->data['type'])->orderBy('id', 'desc')->first();
        }

        return $this->previousEmail ? Arr::get($this->previousEmail->content, $contentAttribute) : $default;
    }

    /**
     * @return array
     */
    private function buildMiddleBannerData()
    {
        $middleBannerFilepath = $this->data['middle_banner']['filepath']
            ?? $this->loadFromLastEmail('middle_banner.filepath');

        $middleBannerEnabled =  Arr::get($this->data, 'middle_banner.enabled', false)
            ?? $this->loadFromLastEmail('middle_banner.enabled', false);

        $middleBannerUrl = $this->data['middle_banner']['url']
            ?? $this->loadFromLastEmail('middle_banner.url');

        return [
            'enabled' => $middleBannerEnabled,
            'filepath' => $middleBannerFilepath,
            'fileurl' => $this->getUrl($middleBannerFilepath),
            'url' => $middleBannerUrl,
        ];
    }

    /**
     * @return array
     */
    private function buildBottomBannerData()
    {
        $bottomBannerFilepath = $this->data['bottom_banner']['filepath']
            ?? $this->loadFromLastEmail('bottom_banner.filepath');

        $bottomBannerEnabled = Arr::get($this->data, 'bottom_banner.enabled', false)
            ?? $this->loadFromLastEmail('bottom_banner.enabled', false);

        $bottomBannerUrl = $this->data['bottom_banner']['url']
            ?? $this->loadFromLastEmail('bottom_banner.url');

        return [
            'enabled' => $bottomBannerEnabled,
            'filepath' => $bottomBannerFilepath,
            'fileurl' => $this->getUrl($bottomBannerFilepath),
            'url' => $bottomBannerUrl,
        ];
    }

    /**
    * @return array
    */
    private function buildArticlesData(string $type)
    {
        $articleIds = array_filter($this->data[$type]['article_ids'] ?? []);
        $service = brandService($this->data['brand']);

        $articles = [];

        foreach ($articleIds as $articleId) {
            $articles[] = $service->getContent($articleId, 3);
        }

        return [
            'data' => collect($articles),
            'enabled' => Arr::get($this->data, "{$type}.enabled", true),
        ];
    }

    /**
     * @return array
     */
    private function buildHeadlinesData()
    {
        return $this->buildArticlesData('headlines');
    }

    /**
     * @return array
     */
    private function buildHighlightsData()
    {
        return $this->buildArticlesData('highlights');
    }

    /**
     * @return array
     */
    private function buildFeaturesData()
    {
        return $this->buildArticlesData('features');
    }

    /**
     * @return array
     */
    private function buildAnalysisData()
    {
        return $this->buildArticlesData('analysis');
    }

    /**
    * @return array
    */
    private function buildTiplinesData()
    {
        return $this->buildArticlesData('tiplines');
    }

    private function buildAdBannerData()
    {
        $adBannerFilepath = $this->data['ad_banner']['filepath']
            ?? $this->loadFromLastEmail('ad_banner.filepath');

        $adBannerEnabled = Arr::get($this->data, 'ad_banner.enabled', false)
            ?? $this->loadFromLastEmail('ad_banner.enabled', false);

        $adBannerUrl = $this->data['ad_banner']['url']
            ?? $this->loadFromLastEmail('ad_banner.url');

        return [
            'enabled' => $adBannerEnabled,
            'filepath' => $adBannerFilepath,
            'fileurl' => $this->getUrl($adBannerFilepath),
            'url' => $adBannerUrl,
        ];
    }

    /**
     * @return array
     */
    private function buildCommonData()
    {
        $host = $this->brand->machine_name;

        return [
            'unpersisted' => true,
            'config' => $this->config,
            'brand' => $this->brand,
            'type' => $this->data['type'],
            'address_from' => config("hosts.{$host}.email_from.address"),
            'name_from' => config("hosts.{$host}.email_from.name"),
            'subject' => $this->data['subject'],
            'is_review' => true,
            'machine_name' => $this->data['brand'],
            'content' => $this->buildSpecificEmailTypeData(),
            'send_date' => \Carbon\Carbon::parse($this->data['send_date'])->format('d F Y'),
        ];
    }

    /**
     * @return array
     */
    private function buildEditorsRoundUpData()
    {
        return [
            'enabled' => Arr::get($this->data, 'editors_round_up.enabled', false),
            'data' => $this->data['editors_round_up']['value'],
        ];
    }

    /**
     * @return array
     */
    private function buildTextPromotionData()
    {
        return [
            'enabled' => Arr::get($this->data, 'text_promotion.enabled', false),
            'data' => $this->data['text_promotion']['value'],
        ];
    }

    /**
     * @return array
     */
    private function buildIamMarketData()
    {
        return [
            'enabled' => Arr::get($this->data, 'iam_market.enabled', false),
            'title' => Arr::get($this->data, 'iam_market.title', ''),
            'introduction' => Arr::get($this->data, 'iam_market.introduction', ''),
            'iptech' => [
                'name' => Arr::get($this->data, 'iam_market.iptech.name', ''),
                'link' => Arr::get($this->data, 'iam_market.iptech.link', ''),
                'image' => Arr::get($this->data, 'iam_market.iptech.image', ''),
                'description' => Arr::get($this->data, 'iam_market.iptech.description', ''),
            ],
        ];
    }

    /**
     * @return array
     */
    private function buildIndustryReportsData()
    {
        return $this->buildArticlesData('industry_reports');
    }

    /**
     * @return array
     */
    private function buildInternationalReportsData()
    {
        return $this->buildArticlesData('international_reports');
    }

    /**
     * @return array
     */
    private function buildContributorsData()
    {
        $enabled = Arr::get($this->data, 'contributors.enabled', false);
        $data = collect();

        if ($enabled) {
            if (request()->filled('brand')) {
                $brand = request()->get('brand');
            } else {
                $brand = $this->data['brand'];
            }

            $type = request()->get('type');

            $firms = Firm::fetchContributors($brand, $type);

            $data = $firms
                ->filter(function (Firm $firm) {
                    return $firm->getIndustryJurisdiction();
                })
                ->mapToGroups(function (Firm $firm) {

                    $industryJurisdiction = $firm->getIndustryJurisdiction();

                    return [$industryJurisdiction->getName() => $firm];
                })
                ->sortKeys();
        }

        return compact('enabled', 'data');
    }

    /**
     * @return array
     */
    private function buildSpecificEmailTypeData()
    {
        $data = [];
        foreach ($this->config['specific_blocks'] as $blockName) {
            $method = 'build' . Str::camel($blockName) . 'Data';
            $data[$blockName] = $this->$method();
        }

        return $data;
    }

    /**
     * @return array
     */
    private function buildLegalUpdatesData()
    {
        return $this->buildArticlesData('legal_updates');
    }

    /**
     * @return array
     */
    private function buildEventsData()
    {

        return [
            'enabled' => Arr::get($this->data, 'events.enabled', false),
            'event_1' => [
                'title' => Arr::get($this->data, 'events.event_1.title', ''),
                'url' => Arr::get($this->data, 'events.event_1.url', ''),
                'url_text' => Arr::get($this->data, 'events.event_1.url_text', ''),
                'location' => Arr::get($this->data, 'events.event_1.location'),
                'description' => Arr::get($this->data, 'events.event_1.description', ''),
                'filepath' => $this->getUrl(Arr::get($this->data, 'events.event_1.filepath', '')),
            ],
            'event_2' => [
                'title' => Arr::get($this->data, 'events.event_2.title', ''),
                'url' => Arr::get($this->data, 'events.event_2.url', ''),
                'url_text' => Arr::get($this->data, 'events.event_2.url_text', ''),
                'location' => Arr::get($this->data, 'events.event_2.location'),
                'description' => Arr::get($this->data, 'events.event_2.description', ''),
                'filepath' => $this->getUrl(Arr::get($this->data, 'events.event_2.filepath', '')),
            ],
            'event_3' => [
                'title' => Arr::get($this->data, 'events.event_3.title', ''),
                'url' => Arr::get($this->data, 'events.event_3.url', ''),
                'url_text' => Arr::get($this->data, 'events.event_3.url_text', ''),
                'location' => Arr::get($this->data, 'events.event_3.location'),
                'description' => Arr::get($this->data, 'events.event_3.description', ''),
                'filepath' => $this->getUrl(Arr::get($this->data, 'events.event_3.filepath', '')),
            ],
            'event_4' => [
                'title' => Arr::get($this->data, 'events.event_4.title', ''),
                'url' => Arr::get($this->data, 'events.event_4.url', ''),
                'url_text' => Arr::get($this->data, 'events.event_4.url_text', ''),
                'location' => Arr::get($this->data, 'events.event_4.location'),
                'description' => Arr::get($this->data, 'events.event_4.description', ''),
                'filepath' => $this->getUrl(Arr::get($this->data, 'events.event_4.filepath', '')),
            ],
        ];
    }

    /**
     * @return array
     */
    private function buildThoughtLeadershipData()
    {
        return $this->buildArticlesData('thought_leadership');
    }

    /**
     * @return array
     */
    private function buildEditorsPickOfDayData()
    {
        return [
            'enabled' => Arr::get($this->data, 'editors_pick_of_day.enabled', false),
            'value' => $this->data['editors_pick_of_day']['value'],
        ];
    }

    private function buildNewsAndUpdatesData()
    {
        return $this->buildArticlesData('news_and_updates');
    }

    /**
     * @param      $filePath
     * @param null $default
     * @return string
     */
    private function getUrl($filePath, $default = null)
    {
        return $filePath
            ? Storage::disk(config('email.config.disk_name'))->url(ltrim($filePath, '/'))
            : $default;
    }

    /**
     * @return array
     */
    private function buildFocusInsightsData()
    {
        return $this->buildArticlesData('focusInsights');
    }
}
