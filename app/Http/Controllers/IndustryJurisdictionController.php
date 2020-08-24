<?php

namespace App\Http\Controllers;

use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\IndustryJurisdiction;
use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class IndustryJurisdictionController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        if (! $this->shouldRenderInternationalReports()) {
            abort(404);
        }

        parent::__construct();
    }

    /**
     * @return View
     */
    public function index($industryJurisdiction = null)
    {

        $tag = null;
        if ($industryJurisdiction) {
            $tags = cacheGroupTags(IndustryJurisdiction::class);
            $tag = $tags->firstWhere('slug', $industryJurisdiction);
        }

        $data = $this->cacheResponseData(function () use ($tag) {
            $service = brandService();
            $search = $service->newSearch();
            $search->setPageSize(500);
            $search->setTagIds([
                [ArticleType::TAG_ID_INDUSTRY_REPORT],
                [ArticleType::TAG_ID_INTERNATIONAL_REPORT],
            ]);

            $reports = $service->run($search, 10)->hydrate();
            $reports = $reports->filter(function (Article $article) use ($tag) {
                if (! $firm = $article->getFirms()->first()) {
                    return null;
                }

                if ($ij = $firm->getIndustryJurisdiction()) {
                    if ($tag && ($tag['tagId'] !== $ij->getTagId())) {
                        return null;
                    }

                    return true;
                }

                return null;
            });

            $reports->transform(function (Article $item) {
                $firm = $item->getFirms()->first();
                $ij = $firm->getIndustryJurisdiction();

                return array_merge(
                    Arr::only(
                        $item->toArray(),
                        [
                            "id",
                            "title",
                            "headline",
                            "precis",
                            "sourceLink",
                            "publishedFrom",
                        ]
                    ),
                    [
                        'firm' => $firm->getName(),
                        'firmUrl' => $firm->getWebsite(),
                        'ijName' =>  $ij->getName(),
                        'ijSlug' =>  $ij->getSlug(),
                    ]
                );
            });

            $industryJurisdictions = $reports->groupBy('firm');

            $dropdown = $industryJurisdictions->mapWithKeys(function ($ij) {
                $item = $ij->first();
                return [$item['ijSlug'] => $item['ijName']];
            })->sort()->all();

            $tagIds = ArticleType::TAG_ID_INTERNATIONAL_REPORT;

            return compact('industryJurisdictions', 'dropdown', 'tagIds');
        });

        if ($data['industryJurisdictions']->isEmpty()) {
            return redirect()->route('industry-jurisdictions.index');
        }

        return view(
            $tag ? 'industry-jurisdiction.show' : 'industry-jurisdiction.index',
            $data
        );
    }

    /**
     * @param IndustryJurisdiction $industryJurisdiction
     * @return View
     */
    public function archive(IndustryJurisdiction $industryJurisdiction)
    {
        if ($industryJurisdiction->getArchivedFirms()->isEmpty()) {
            abort(404);
        }

        return view('industry-jurisdiction.archive', compact('industryJurisdiction'));
    }

    /**
     * @return bool
     */
    private function shouldRenderInternationalReports()
    {
        $internationalReportsMenuItemName = 'International Reports';
        $allMenuItems = Arr::flatten(get_host_config('main_menu'));

        return in_array($internationalReportsMenuItemName, $allMenuItems);
    }
}
