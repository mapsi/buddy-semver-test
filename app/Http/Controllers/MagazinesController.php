<?php

namespace App\Http\Controllers;

use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\MagazineSection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MagazinesController extends Controller
{
    public function index()
    {
        $data = $this->cacheResponseData(function () {
            $search = $this->service->newSearch();
            $search->setTagIds([Magazine::ENTITY_TYPE_TAG_ID]);

            $magazines = $this->service->run($search)->hydrate();

            return compact('magazines');
        });

        $data['years'] = $this->getMagazineListYears($data['magazines']);

        return view('magazines.index', $data);
    }

    private function getMagazineListYears(Collection $magazines): Collection
    {
        //Do not consider year of the current issue
        $magazineList = $magazines->slice(1);

        return $magazineList->map(function ($magazine, $key) {
            return $magazine->getYear();
        })->unique();
    }

    public function download(Request $request)
    {
        $sourceLink = str_replace('/download', '', $request->path());
        $sourceLink = preg_replace('/Magazine/', 'Magazine/Issue', $sourceLink);

        $magazine = $this->service->getContentBySourceLink($sourceLink);

        if (Str::contains(url()->previous(), 'login')) {
            if (! $magazine->canView($request->user())) {
                return redirect($magazine->getCanonicalUrl())->with('subscribe', true);
            }

            return redirect($magazine->getCanonicalUrl())->with('startDownload', true);
        }

        if (! $magazine->canView($request->user())) {
            abort(403, 'Cannot download magazines.');
        }

        //check if old magazine and legacy link exists in mapping file
        $mapping = include 'MagazineMappingFile/magazinepdfs.php';
        $magguid = $magazine->getOriginalId();

        if (array_key_exists($magguid, $mapping)) {
            $path = $mapping[$magguid];
        } else {
            $path = $magazine->getPdf();
        }

        $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($magazine->getTitle())) . '.pdf';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
        }, $filename);
    }

    public function showMagazineSection($section)
    {
        $sections = [
            'CountryCorrespondent' => MagazineSection::TAG_ID_COUNTRY_CORRESPONDENT,
            'Roundtable' => MagazineSection::TAG_ID_ROUNDTABLE,
            'ManagementReport' => MagazineSection::TAG_ID_MANAGEMENT_REPORT,
        ];

        $tagId = $sections[$section];

        $tag = Service::getGroupTagsByTagId($tagId);

        $magazines = $this->cacheResponseData(function () use ($tag) {
            $magazineSearch = $this->service->newSearch();
            $magazineSearch
                ->setTagIds([Magazine::ENTITY_TYPE_TAG_ID]);

            $magazineResults = $this->service->run($magazineSearch)->hydrate();

            $magazines = collect();
            foreach ($magazineResults as $magazine) {
                $search = $this->service->newSearch();
                $search
                    // save the magazine id for next request
                    ->setRelationIds([$magazine->getId()])
                    ->setTagIds([$tag['tagId']])
                    ->setSort(Search::SORT_TYPE_TITLE);
                $articles = $this->service->run($search)->hydrate();

                if ($articles->isNotEmpty()) {
                    $magazines->push(compact('magazine', 'articles'));
                }
            }

            return $magazines;
        });

        $chunks = 4;
        if ($section == 'Roundtable') {
            $chunks = 21;
        }

        return view('magazines.sections.section-show-' . strtolower($section), [
            'magazines' => $magazines->paginate($chunks),
            'magazine_section' => MagazineSection::$title[$tag['tagId']],
            'tagIds' => $tag['tagId'],
        ]);
    }
}
