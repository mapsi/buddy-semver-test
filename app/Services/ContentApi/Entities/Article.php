<?php

namespace App\Services\ContentApi\Entities;

use App\Auth\Traits\ComparesPermissions;
use App\Classes\BotDetect;
use App\Models\Feature;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\SubscriptionLevel;
use App\Models\Team;
use App\Models\User;
use App\Services\ContentApi\Interfaces\Searchable;
use App\Services\ContentApi\TagGroups\ArticleCategory;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\MagazineSection;
use App\Services\ContentApi\TagGroups\Sector;
use App\Services\ContentApi\TagGroups\SubBrand;
use App\Services\ContentApi\TagGroups\Topic;
use App\Services\ContentApi\Traits\HasAuthors;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasPdf;
use App\Services\ContentApi\Traits\HasRegions;
use App\Services\ContentApi\Traits\HasSections;
use App\Services\ContentApi\Traits\HasFirmProfiles;
use App\Services\ContentApi\Traits\HasWorkAreas;
use App\Services\ContentApi\TagGroups\EntityType;
use Lang;
use DOMDocument;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Article extends DataEntity implements Searchable
{
    use HasAuthors;
    use HasFirms;
    use HasPdf;
    use HasRegions;
    use HasSections;
    use HasFirmProfiles;
    use HasWorkAreas;
    use ComparesPermissions;

    const ENTITY_TYPE_TAG_ID = 2822;

    public function __get($name)
    {
        if ($name === 'resource') {
            return $this;
        }
    }

    public function getTitle()
    {
        // return isset($this->highlight->headline[0])
        //     ? strip_tags($this->highlight->headline[0], '<span>')
        //     : e($this->headline) ?: $this->title;
        return $this->data['title'];
    }

    public function getHeadline()
    {
        return $this->data['title'];
    }

    public function getOriginalId()
    {
        return $this->data['originalId'];
    }

    public function getPrecis()
    {
        return Arr::get($this->data, 'precis');
    }

    public function getEmailPrecis()
    {
        return Arr::get($this->data, 'emailPrecis');
    }

    public function getTags()
    {
        return $this->data['tags'];
    }

    /**
     * @return string
     */
    public function getBreadcrumbSection()
    {
        if ($this->isGcrUsaContent()) {
            return lang('gcr_usa');
        }

        if ($this->isFromNewsSection()) {
            return lang('news');
        }

        if ($this->isFromFeaturedSection()) {
            return lang('features');
        }

        return lang('news');
    }

    public function isFromNewsSection()
    {
        return Arr::first($this->data['tags'], function ($tag) {
            return $tag['name'] === 'News';
        });
    }

    public function isFromFeaturedSection()
    {
        return Arr::first($this->data['tags'], function ($tag) {
            return $tag['name'] === 'Features';
        });
    }

    /**
     * @return string
     */
    public function getBreadcrumbUrl()
    {
        if ($this->isGcrUsaContent()) {
            return route('usa.index');
        }

        if ($this->isFromNewsSection()) {
            return route('articles.news');
        }

        if ($this->isFromFeaturedSection()) {
            return route('articles.features');
        }

        return route('articles.news');
    }

    public function isFree(): bool
    {
        return ! $this->isPremium() && $this->getPaywallType() == 'free';
    }

    public function isPremium(): bool
    {
        return (bool) Arr::first($this->data['tags'], function ($tag) {
            return ($tag['typeId'] === ArticleCategory::TAG_GROUP_ID)
                && ($tag['tagId'] === ArticleCategory::TAG_ID_PREMIUM);
        });
    }

    /**
     * @return int|mixed|string
     */
    public function getPaywallType()
    {

        if (in_array(active_host(), ['gdr', 'gbrr', 'grr', 'gcr'])) {
            return 'free';
        }

        $rules = get_host_config('access_rules', []);
        $result = get_host_config('access_rules_default', 'subscriber');

        $tagGroup = ArticleType::class;

        if ($this->getTagGroup(MagazineSection::class)->isNotEmpty()) {
            $tagGroup = MagazineSection::class;
        }

        foreach ($this->filterByTagGroupId($tagGroup::TAG_GROUP_ID) as $tag) {
            $type = Arr::get($rules[$tagGroup], $tag['tagId'], 'free');

            if ($type == 'free-forced') {
                //dont go back to free if we are metered
                return 'free';
            }

            if ($type === 'metered') {
                //dont bother with the rest as we are done

                $result = 'metered';
                break;
            }

            $result = $type;
        }

        return $result;
    }

    public function getMagazine()
    {
        if ($magazine = $this->getRelations(Magazine::class)->first()) {
            // we need to refetch the magazine as otherwise it doesn't contain the precis
            $magazine = $this->service->getContent($magazine->getId());

            return $magazine;
        }

        return $this->getRelations(Supplement::class)->first();
    }

    public function isPaywallLoggedIn(): bool
    {
        return $this->getPaywallType() == 'logged_in';
    }

    public function isMetered(): bool
    {
        return ! $this->isPremium() && $this->getPaywallType() == 'metered';
    }

    public function getSectors()
    {
        return $this->getTagGroup(Sector::class);
    }

    public function getTopics()
    {
        return $this->getTagGroup(Topic::class);
    }

    public function hasArticleType(int $articleType)
    {
        $types = $this->getArticleTypes();

        return (bool) Arr::first($types, function ($tag) use ($articleType) {
            return $tag['tagId'] === $articleType;
        });
    }

    public function getArticleTypes()
    {
        return $this->filterByTagGroupId(ArticleType::TAG_GROUP_ID);
    }

    public function getFormattedCredits()
    {
        $formatted = null;

        if ($this->getMediaCaption() && $this->getMediaCredits()) {
            $formatted = $this->getMediaCaption() . ' (' . $this->getMediaCredits() . ')';
        } elseif ($this->getMediaCaption()) {
            $formatted = $this->getMediaCaption();
        } elseif ($this->getMediaCredits()) {
            $formatted = $this->getMediaCredits();
        }

        return $formatted;
    }

    public function getMediaCredits()
    {
        return $this->getInfo('LeadImageCredits')
            ? 'Credit: ' . $this->getInfo('LeadImageCredits')
            : $this->getInfo('LeadImageCredits');
    }

    public function getMediaCaption()
    {
        return $this->getInfo('LeadImageCaption');
    }

    public function isGcrUsaContent()
    {
        return Arr::first($this->data['tags'], function ($tag) {
            return $tag['tagId'] === SubBrand::TAG_ID_GCR_USA;
        });
    }

    public function isGcrAsiaContent()
    {
        return Arr::first($this->data['tags'], function ($tag) {
            return $tag['tagId'] === SubBrand::TAG_ID_GCR_ASIA;
        });
    }

    public function getView()
    {
        $view = 'articles.show';

        if ($this->getLayout() && $this->getLayout() != 'default') {
            $view = 'articles.layouts.' . $this->getLayout();
            if (! view()->exists($view)) {
                $view = 'articles.show';
            }
        }

        if ($this->isGcrUsaContent()) {
            $view = 'usa.show';
        }

        if ($this->isGcrAsiaContent()) {
            $view = 'asia.show';
        }

        return $view;
    }

    public function getLayout()
    {
        return $this->getInfo('Layout');
    }

    public function canView(User $user): bool
    {
        $article = $this;

        if ($article->isFree()) {
            return true;
        }

        if ($user->admin) {
            return true;
        }

        if (SubscriptionLevel::usingThis(active_host())) {
            return $this->comparePermissions($user);
        }

        if ($user->isSubscriber('articles')) {
            return true;
        }

        if ($article->isPaywallLoggedIn()) {
            return auth()->user();
        }

        if ($user->id && $article->isMetered() && $this->hasReadLessThanTwoArticlesInLastMonth($user)) {
            return true;
        }

        if (
            active_host() === 'gdr'
            && get_host_config('subscribed_to_login', false)
            && $user->isVerified()
        ) {
            return true;
        }

        $botdetect = new BotDetect(Request());
        if ($botdetect->validate()) {
            return true;
        }

        return false;
    }

    protected function hasReadLessThanTwoArticlesInLastMonth(User $user): bool
    {

        return $user->views()->lastMonth()->freeRead()->where(function ($query) {
            $query->orWhere(function ($query) {
                $query->where('brand_machine_name', active_host());
                $query->where('views.routable_type', class_basename($this));
            });
        })->count() <= 2;
    }

    public function getMagazineSections()
    {
        return $this->filterByTagGroupId(MagazineSection::TAG_GROUP_ID);
    }

    public function magazineSectionIs(int $magazineSectionId)
    {
        return in_array($magazineSectionId, array_column($this->getMagazineSections(), 'tagId'));
    }

    public function getFlagCssClass()
    {
        $badregions = [
            //"Africa & Middle East" => 2117,
            //"European Union" => 2184,
            //"Europe" => 2172,
            //"Asia-Pacific" => 2149,
            //"International" => 2228,
            "Tanzania, United Republic of" => 'Tanzania',
            //"Latin America & Caribbean" => 2210,
            //"North America" => 2225,
            "United States of America" => 'United States',
            "South Korea" => 'Republic of Korea',
            "Iran, Islamic Republic of" => 'Iran',
            "Côte d'Ivoire" => 'Cote D\'ivoire',
            "Montenegro" => 'Republic of Montenegro',
            // "Laos" => 'Lao People\'s Rep.',
            "Syria" => 'Syrian Arab Republic',
            "Serbia" => 'Republic of Serbia',
            "Macedonia, the former Yugoslav Republic of" => 'Macedonia',
            //"Caribbean" => 2214,
        ];

        $region = $this->getCountryAttribute();

        if (! $region) {
            return false;
        }
        switch ($region->getName()) {
            case 'European Union':
                return 'EUR';
                break;

            case 'International':
                return 'INT';
                break;

            case 'Laos': //has a . at the end of the name
                return 'LA';
                break;

            default:
                return trans('countries.reversed_list.' . (isset($badregions[$region->getName()]) ? $badregions[$region->getName()] : $region->getName()));
                break;
        }
    }

    public function getCountryAttribute()
    {
        $badregions = [
            //"Africa & Middle East" => 2117,
            //"European Union" => 2184,
            //"Europe" => 2172,
            //"Asia-Pacific" => 2149,
            //"International" => 2228,
            "Tanzania, United Republic of" => 'Tanzania',
            //"Latin America & Caribbean" => 2210,
            //"North America" => 2225,
            "United States of America" => 'United States',
            "South Korea" => 'Republic of Korea',
            "Iran, Islamic Republic of" => 'Iran',
            "Côte d'Ivoire" => 'Cote D\'ivoire',
            "Montenegro" => 'Republic of Montenegro',
            // "Laos" => 'Lao People\'s Rep.',
            "Syria" => 'Syrian Arab Republic',
            "Serbia" => 'Republic of Serbia',
            "Macedonia, the former Yugoslav Republic of" => 'Macedonia',
            //"Caribbean" => 2214,
        ];
        $region = $this->getRegions()->filter(function ($r) use ($badregions) {
            return Lang::has('countries.reversed_list.' . (isset($badregions[$r->getName()]) ? $badregions[$r->getName()] : $r->getName())) || $r->getName() == 'European Union' || $r->getName() == 'Laos' || $r->getName() == 'International';
        })->first(); //we pick the first of them always even if there is more than one country

        return $region;
    }

    public function isCopublished()
    {
        echo __METHOD__;
        return false;
    }


    public function getStandFirst()
    {
        return Arr::get($this->data, 'standFirst');
    }

    public function hasCoPublishedMagazineSection()
    {
        return in_array('Co-published', array_column($this->getMagazineSections(), 'name'));
    }

    public function __invoke()
    {
        return $this->data['title'];
    }

    public function getSearchableArray()
    {
        $imageUrl = $this->getMediaUrl('sm');
        $isFirm = false;

        if (
            $this->hasArticleType(ArticleType::TAG_ID_INSIGHT) ||
            $this->hasArticleType(ArticleType::TAG_ID_FOCUS_INSIGHT)
        ) {
            $firm = $this->getFirms()->first();
            $firmImage = isset($firm) ? $firm->getMediaUrl() : null;
            if (! empty($firmImage)) {
                $imageUrl = $firmImage;
                $isFirm = true;
            }
        }

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

        $subBrands = [SubBrand::TAG_ID_GCR_USA => "GCR USA", SubBrand::TAG_ID_GCR_ASIA => "GCR ASIA"];
        $tags = $this->getTags();

        $subBrandTags = [];
        foreach ($subBrands as $key => $subBrand) {
            if (array_search($key, array_merge(array_keys($tags), array_column($tags, 'tagId')))) {
                $subBrandTags[] = $subBrand;
            }
        }

        return [
            'id' => $this->getId(),
            'publishedFrom' => $this->getPublicationDate(),
            'title' => $this->getTitle(),
            'headline' => $this->getHeadline(),
            'precis' => $this->getPrecis(),
            'url' => $this->getCanonicalUrl(),
            'imageUrl' => $imageUrl,
            'isFirm' => $isFirm,
            'editionName' => $editionName,
            'editionUrl' => $editionUrl,
            'subBrandTags' => $subBrandTags,
            'type' => $this->getEntityType()
        ];
    }

    public function getBody()
    {
        return $this->treatIframesForCookiePro(
            in_array(active_host(), ['wtr', 'iam'])
                ? $this->resolveLegacyLinksinBody()
                : Arr::get($this->data, 'body')
        );
    }

    private function treatIframesForCookiePro($body): string
    {
        return preg_replace_callback(
            '/<iframe(?<p1>( [a-z]+="([^"]+)")*) (src="(?<src>[^"]+)")(?<p2>( [a-z]+="([^"]+)"))*>/',
            function ($matches) {
                ['p1' => $p1, 'src' => $src, 'p2' => $p2] = $matches;

                return "<iframe$p1 data-src='$src'$p2 class='optanon-category-2'>";
            },
            $body
        );
    }

    private function resolveLegacyLinksinBody()
    {
        $b = Arr::get($this->data, 'body');

        //Using code from ContentSection->fetchImagesInText() to ensure that this behaves similarly(-ish)

        if (strpos($b, '/sites/default/files') == 0) {
            return $b;
        }
        try {
            $dom_document = new DOMDocument();
            @$dom_document->loadHTML('<?xml encoding="UTF-8">' . $b, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $dom_document->preserveWhiteSpace = false;
            $dom_document->formatOutput = true;

            foreach ($dom_document->childNodes as $item) {
                if ($item->nodeType == XML_PI_NODE) {
                    $dom_document->removeChild($item); // Remove the hack
                }
            }

            $dom_document->encoding = 'UTF-8'; // Set the encoding

            // <a href
            $links = $dom_document->getElementsByTagName('a');

            foreach ($links as $element) {
                $old_href = $element->getAttribute('href');

                if (strpos($old_href, '/sites/default/files') !== 0) {
                    continue;
                }

                // Sometimes we get files with massive filenames. This shortens them.
                $filename = pathinfo($old_href, PATHINFO_FILENAME);
                $filename = substr($filename, 0, 100);

                $basename = $filename . '.' . pathinfo($old_href, PATHINFO_EXTENSION);

                $key = $this->getOriginalId() . '---' . $basename;

                $articleimages = include 'DataFiles/articleimages.php';

                if (array_key_exists($key, $articleimages)) {
                    $element->setAttribute('href', $articleimages[$key]);
                }
            }

            // <img src
            $images = $dom_document->getElementsByTagName('img');

            foreach ($images as $element) {
                $old_src = $element->getAttribute('src');

                if (strpos($old_src, '/sites/default/files') !== 0) {
                    continue;
                }

                $pos = strrpos($old_src, "/", -1);
                $filename = substr($old_src, $pos + 1, strlen($old_src) - $pos - 1);
                $key = $this->getOriginalId() . '---' . $filename;

                $articleimages = include 'DataFiles/articleimages.php';

                if (array_key_exists($key, $articleimages)) {
                    $element->setAttribute('src', $articleimages[$key]);
                }
            }

            return $dom_document->saveHTML();
        } catch (Exception $e) {
            report($e);

            return $b;
        }
    }

    public function requiresPermissions(): array
    {
        $permissions = [];

        $subBrands = $this->getTagGroup(SubBrand::class);
        $subBrands->each(function (SubBrand $item) use (&$permissions) {
            $tagId = $item->getTagId();

            switch ($tagId) {
                case SubBrand::TAG_ID_GCR_ASIA:
                    $permissions[] = Feature::TYPE_GCR_ASIA;
                    break;
                case SubBrand::TAG_ID_GCR_USA:
                    $permissions[] = Feature::TYPE_GCR_USA;
                    break;
            }
        });

        if (count($permissions) > 0) {
            // we have sub brand specific rules that trump all others.
            return $permissions;
        }

        $types = $this->getTagGroup(ArticleType::class);

        $types->each(function (ArticleType $item) use (&$permissions) {
            $tagId = $item->getTagId();

            switch ($tagId) {
                case ArticleType::TAG_ID_NEWS:
                    $permissions[] = Feature::TYPE_NEWS;
                    break;
                case ArticleType::TAG_ID_FEATURES:
                    $permissions[] = Feature::TYPE_FEATURES;
                    break;
                case ArticleType::TAG_ID_CONFERENCE_COVERAGE:
                    $permissions[] = Feature::TYPE_CONFERENCE_REPORT;
                    break;
                case ArticleType::TAG_ID_DATA_SURVEYS:
                case ArticleType::TAG_ID_40_UNDER_40:
                case ArticleType::TAG_ID_RATING_ENFORCEMENT:
                case ArticleType::TAG_ID_CORPORATE_COUNSEL:
                case ArticleType::TAG_ID_EMERGING_ENFORCERS:
                case ArticleType::TAG_ID_CORPORATE_COUNSEL:
                case ArticleType::TAG_ID_WOMEN_IN_ANTITRUST:
                    $permissions[] = Feature::TYPE_SURVEYS;
                    break;
                case ArticleType::TAG_ID_GRR_100:
                case ArticleType::TAG_ID_GCR_100:
                    $permissions[] = Feature::TYPE_GXR_100_CURRENT;
                    break;
                case ArticleType::TAG_ID_INTERVIEWS:
                    $permissions[] = Feature::TYPE_INTERVIEW;
                    break;
                case ArticleType::TAG_ID_MAGAZINE_ARTICLE:
                    $permissions[] = Feature::TYPE_MAGAZINE_CONTENT;
                    break;
            }
        });

        if (Str::startsWith($this->data['sourceLink'], 'https://globalrestructuringreview.com/guide/')) {
            // TODO: Dirty dirty hack for GRR launch - there must be a better way than this...
            $permissions[] = Feature::TYPE_GUIDES;
        }

        if (Str::startsWith($this->data['sourceLink'], 'https://globalrestructuringreview.com/review/')) {
            // TODO: Dirty dirty hack for GRR launch - there must be a better way than this...
            $permissions[] = Feature::TYPE_REGIONAL_REVIEWS;
        }

        return $permissions;
    }
}
