<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Entities\Profiles\FirmProfile;
use App\Services\ContentApi\Entities\Section;
use App\Services\ContentApi\Entities\Profiles\OrganisationProfile;
use App\Services\ContentApi\Entities\Profiles\PersonProfile;
use App\Services\ContentApi\Interfaces\HasRegionsInterface;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class EditionController
{
    public function show(Request $request, Service $service)
    {
        $path = $request->path();

        $entity = $service->getContentBySourceLink($path);

        $template = $entity->getSeries()->getTemplate();

        if ($template === 'GXR100') {
            return $this->showGxr100($entity);
        }

        if ($template === 'Handbook') {
            return $this->showHandbook($entity, $service);
        }

        if ($template === 'Country Surveys') {
            return $this->showCountrySurveys($entity, $service);
        }

        //'Custom Person Profile A' or 'Default' templates
        return $this->showProfiles($entity, $template);
    }


    private function showProfiles(Edition $entity, string $template)
    {
        $sections = $entity->fetchSections();
        $hasSections = $sections->isNotEmpty();

        // check for archive
        $hasArchive = false;
        $otherEditions = collect();
        $editionLayout = $entity->getSeries()->getInfo('EditionLayout');
        $isParallelType = $editionLayout == 'parallel';

        if ($isParallelType) {
            $otherEditions = $entity->getSeries()->getOtherEditions($entity);
            if ($otherEditions->count() > 0) {
                $hasArchive = true;
            }
        }

        $sectionCount = 0;
        // new
        if ($hasSections) {
            $profileTypes = [
                class_basename(OrganisationProfile::class),
                class_basename(PersonProfile::class)
            ];

            $profiles = collect();

            $sections->each(function (Section $section) use ($profiles, $profileTypes, &$sectionCount) {
                if ($section->getTemplate() == Section::PROFILE_LIST_TEMPLATE) {
                    $profiles->push($section->getContentsByType($profileTypes));
                    $sectionCount++;
                } else {
                    $profiles->push(collect());
                }
            });


            $availableCountries = collect();

            $profiles->each(function (Collection $profileList) use ($availableCountries) {
                $availableCountries->push($this->getAvailableCountries($profileList));
            });

            $profileType = 'people';

            $viewData = compact('entity', 'profiles', 'availableCountries', 'profileType', 'sections', 'hasArchive', 'otherEditions', 'sectionCount');

            return view('editions.show_profiles', $viewData);
            // legacy
        } else {
            $profiles = $entity->fetchProfiles();

            $availableCountries = $this->getAvailableCountries($profiles);

            $profileType = 'people';

            if ($template == 'Organisation' || Str::contains($entity->getSeries()->getTitle(), 'Enforcer Hub')) {
                $profileType = "organisation";
            }

            $viewData = compact('entity', 'profiles', 'availableCountries', 'profileType', 'hasArchive', 'otherEditions');

            return view('editions.show', $viewData);
        }
    }

    private function getAvailableCountries($profiles)
    {
        return $profiles
            ->map(function (HasRegionsInterface $profile) {
                return $profile->getRegions()->first();
            })
            ->filter(function ($country) {
                return $country != null;
            })
            ->unique(function (Country $country) {
                return $country->getTagId();
            })
            ->sortBy->getName()->values();
    }

    private function showHandbook(Edition $entity, Service $service)
    {
        // legacy way - fetch content directly linked to edition
        $articles = $entity->fetchArticles()->sortBy(function (Article $article) {
            return $article->getInfo('EditionOrder');
        });

        // if sections are defined we will output edition using sections
        $sections = $entity->fetchSections();
        $hasSections = $sections->isNotEmpty();

        // check for archive
        $hasArchive = false;
        $otherEditions = collect();
        $editionLayout = $entity->getSeries()->getInfo('EditionLayout');
        $isParallelType = $editionLayout == 'parallel';

        if ($isParallelType) {
            $otherEditions = $entity->getSeries()->getOtherEditions($entity);
            if ($otherEditions->count() > 0) {
                $hasArchive = true;
            }
        }

        $viewData = compact('entity', 'articles', 'hasSections', 'sections', 'hasArchive', 'otherEditions');

        return view('editions.handbook_show', $viewData);
    }

    private function showCountrySurveys(Edition $entity, Service $service)
    {
        $relationId = $entity->getId();
        $contentSearch = $service->newSearch();
        $regionListings = $contentSearch
            ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_COUNTRY_SURVEY])
            ->setRelationIds([$relationId])
            ->setPageSize(20)
            ->setSort(Search::SORT_TYPE_TITLE);

        $result = $service->run($regionListings)->toArray();

        $regions = array_map(function ($region) {
            if (
                $country = Arr::first($region['tags'], function ($item) {
                    return $item['typeId'] === Country::TAG_GROUP_ID;
                })
            ) {
                return [
                    'tagId' => $country['tagId'],
                    'name' => $country['name'],
                ];
            }
        }, $result['items']);

        $filteredRegions = collect($regions)->unique('name')->sortBy('name')->values()->all();

        return view('editions.country_survey_show', compact('entity', 'regions', 'filteredRegions'));
    }

    private function showGxr100(Edition $entity)
    {
        $sections = $entity->fetchSections();

        $firms = $this->getGxr100Firms($sections);

        $featuredFirms = $firms->filter(function ($firm) {
            return $firm->hasProfessionalNotice();
        })->sortBy(function ($firm) {
            return $firm->getName();
        });

        // check for archive
        $hasArchive = false;
        $otherEditions = collect();
        $editionLayout = $entity->getSeries()->getInfo('EditionLayout');
        $isParallelType = $editionLayout == 'parallel';

        if ($isParallelType) {
            $otherEditions = $entity->getSeries()->getOtherEditions($entity);
            if ($otherEditions->count() > 0) {
                $hasArchive = true;
            }
        }

        return view('editions.gxr100', compact('entity', 'sections', 'firms', 'featuredFirms', 'hasArchive', 'otherEditions'));
    }

    private function getGxr100Firms(Collection $sections): Collection
    {
        $firms = $sections->filter(function ($section) {
            return $section->getTemplate() == Section::ALPHABET_FILTER_TEMPLATE;
        });

        $totalFirms  = collect();
        if (isset($firms) && $firms->count() > 0) {
            $totalFirms = $firms->map->getContentsByType([class_basename(OrganisationProfile::class)])->flatten();
        }
        return $totalFirms;
    }

    public function showDescription(Request $request, Service $service)
    {
        $edition = $service->getContentBySourceLink(str_replace('/introduction', '', $request->path()));

        return view('editions.edition_introduction', compact('edition'));
    }

    public function showArticle(Service $service, Request $request)
    {
        $entity = $service->getContentBySourceLink($request->path());

        // fetch edition via Section / else support legacy linkage TODO: remove else
        if ($entity->getSection()) {
            $edition = $entity->getSection()->getEdition();
        } else {
            $search = $service->newSearch();
            $slug = $request->route('edition');
            $search->setSlug($slug);
            $result = $service->run($search);
            $edition = $result->hydrate()->first();
        }

        if (! $edition) {
            // if edition is unpublished fall gracefully
            return abort(404);
        }

        $sections = $edition->fetchSections();
        $firms = $this->getGxr100Firms($sections);

        $featuredFirms = $firms->filter(function ($firm) {
            return $firm->hasProfessionalNotice();
        })->sortBy(function ($firm) {
            return $firm->getName();
        });

        // TODO: make Fabio to allow for bulk fetching of Profiles

        // $sections = $edition->fetchSections();

        // if ($sections->isNotEmpty()) {
        //     $allAuthorIds = collect();
        //     $allFirmIds = collect();
        //     foreach ($sections as $section) {
        //         foreach ($section->getContents() as $content) {
        //             if ($content->getEntityType() == 'Article') {
        //                 $authorIds = $content->getAuthorIds()->flatten();
        //                 $allAuthorIds->push($authorIds);
        //                 //$firmsIds = $content->getFirmIds();
        //             }
        //         }
        //     }
        //     $allAuthorIds = $allAuthorIds->flatten()->unique()->values()->all();

        //     $tagIds = [
        //         Profile::ENTITY_TYPE_TAG_ID,
        //         AuthorProfile::PROFILE_TYPE_TAG_ID,
        //     ];

        //     $search = $service->newSearch();
        //     $search->setTagIds($tagIds);
        //     $search->setRelationIds($allAuthorIds);

        //     $profiles = $service->run($search)->hydrate();

        // }

        $canView = $entity->canView(auth()->user() ?? new User());

        return view('editions.edition_show_article', compact('entity', 'edition', 'canView', 'featuredFirms'));
    }

    public function showPersonProfile(Request $request, Service $service)
    {
        $entity = $service->getContentBySourceLink($request->path());

        // new
        $section = $entity->getSection();
        if (! empty($section)) {
            $edition = $section->getEdition();
            $personProfiles = $section->getContentsByType([class_basename(PersonProfile::class)]);
            // legacy
        } else {
            $edition = $entity->getEdition();
            $personProfiles = $edition
                ->fetchProfiles()
                ->reject(function (PersonProfile $item) use ($entity) {
                    return $item->getName() === $entity->getName();
                });
        }

        $region = $entity->getRegions()->first();

        $canView = $entity->canView(($user = auth()->user()) ?? new User());

        return view(
            $edition->getSeries()->getView(),
            compact('entity', 'edition', 'user', 'canView', 'personProfiles', 'region')
        );
    }

    private function showEnforcerProfile(OrganisationProfile $entity, Edition $edition, $section)
    {
        if (! empty($section)) {
            $organizationProfiles = $section->getContentsByType([class_basename(OrganisationProfile::class)]);
        } else {
            $organizationProfiles = $edition
                ->fetchProfiles()
                ->reject(function (OrganisationProfile $item) use ($entity) {
                    return $item->getName() === $entity->getName();
                });
        }

        $canView = $entity->canView(($user = auth()->user()) ?? new User());

        return view(
            'editions.edition_show_organization_profile_enforcer_hub',
            compact('entity', 'edition', 'user', 'canView', 'organizationProfiles')
        );
    }

    public function showOrganizationProfile(Request $request, Service $service)
    {
        $path = $request->path();
        $entity = $service->getContentBySourceLink($path);

        // new
        $section = $entity->getSection();
        if (! empty($section)) {
            $edition = $section->getEdition();
            // legacy
        } else {
            $edition = $entity->getEdition();
        }

        $template = $edition->getSeries()->getTemplate();
        if ($template == 'GXR100') {
            return $this->showGxr100Profile($entity, $edition);
        }

        if ($template == 'Organisation' || Str::contains($edition->getTitle(), 'Enforcer Hub')) {
            return $this->showEnforcerProfile($entity, $edition, $section);
        }

        return view('editions.edition_show_organization_profile', compact('entity', 'edition'));
    }

    private function showGxr100Profile(OrganisationProfile $entity, Edition $edition)
    {
        $isProfessionalNotice = false;
        $professionalNotice = null;
        if ($entity->hasProfessionalNotice()) {
            $isProfessionalNotice = true;
            $professionalNotice = $entity->getProfessionalNotice();
        }

        $contents = collect([]);

        $sections = $edition->fetchSections();
        $firms = $this->getGxr100Firms($sections);

        $featuredFirms = $firms->filter(function ($firm) {
            return $firm->hasProfessionalNotice();
        })->sortBy(function ($firm) {
            return $firm->getName();
        });

        if ($canView = $entity->canView(($user = auth()->user()) ?? new User())) {
            $contents = $sections->map(function ($section) {
                return $section->getContents();
            })->flatten();
        }

        return view('editions.edition_show_gxr100_organization_profile', compact(
            'entity',
            'edition',
            'user',
            'canView',
            'isProfessionalNotice',
            'professionalNotice',
            'contents',
            'firms',
            'featuredFirms',
        ));
    }

    public function showContentPiecesRegionListing(Request $request, Service $service)
    {
        $path = $request->path();
        $region = $request->route('region');
        $edition = $service->getContentBySourceLink(str_replace('/region/' . $region, '', $path));

        $search = $service->newSearch();
        $search->setRelationIds([$edition->getId()]);
        $search->withContent();
        $result = $service->run($search)->hydrate();
        $filtered = $result->filter(function ($item) use ($region) {
            if ($country = $item->getRegions()->first()) {
                return Str::slug($country->getName()) === $region;
            }

            return false;
        });

        $articles = $filtered->filter(function ($item) {
            return get_class($item) === Article::class;
        });

        $profiles = $filtered->filter(function ($item) {
            return get_class($item) === OrganisationProfile::class;
        })->sortBy(function ($item) {
            return $item->getInfo('EditionOrder');
        });

        if ((! $articles->count()) || (! $profiles->count())) {
            abort(404);
        }

        $article = $articles->first();

        return view(
            'editions.edition_content_pieces_region_listing',
            compact('edition', 'article', 'profiles', 'region')
        );
    }

    public function download(Service $service, Request $request, string $series_type, string $series, string $edition)
    {
        if (auth()->user() || $request->cookie('leadgen') === "completed") {
            $edition = $service->getContentBySourceLink(str_replace('/download', '', $request->path()));

            // below should be refactored after Laravel upgrade >= 5.6 to response()->streamDownload()
            // https://laravel.com/docs/5.6/responses#file-downloads
            $response = response()->stream(function () use ($edition) {
                echo file_get_contents($edition->getDigitalAssetUrl());
            });

            $filename = $edition->getDigitalAssetFileName();

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
                str_replace('%', '', Str::ascii($filename))
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } else {
            return redirect()->route('edition.leadgen', [$series_type, $series, $edition]);
        }
    }

    /**
     * @param string $series_type
     * @param Series  $series
     * @param Edition $edition
     */
    public function showLeadgen(Service $service, Request $request, string $series_type, string $series, string $edition)
    {
        $edition = $service->getContentBySourceLink(str_replace('/form', '', $request->path()));
        return view('editions.edition_leadgen', compact('edition'));
    }

    /**
     * @param string $series_type
     * @param Series  $series
     * @param Edition $edition
     */
    public function leadgenThankYou(Service $service, Request $request, string $series_type, string $series, string $edition)
    {
        $edition = $service->getContentBySourceLink(str_replace('/form/thank-you', '', $request->path()));
        Cookie::queue(Cookie::make('leadgen', 'completed', 20160));
        return view('editions.edition_leadgen_thank_you', compact('edition'));
    }
}
