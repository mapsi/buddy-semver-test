<?php

namespace App\Services\ContentApi\Entities;

use App\Auth\Traits\ComparesPermissions;
use App\Models\Feature;
use App\Models\SubscriptionLevel;
use App\Models\User;
use App\Services\ContentApi\Section;
use App\Services\ContentApi\TagGroups\MagazineSection;
use App\Services\ContentApi\TagGroups\SupplementIssue;
use App\Services\ContentApi\Traits\HasArticles;
use App\Services\ContentApi\Traits\HasAuthors;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasPdf;
use Illuminate\Support\Arr;

abstract class IsMagazine extends DataEntity
{
    use HasArticles;
    use HasAuthors;
    use HasPdf;
    use HasFirms;
    use ComparesPermissions;

    public function getDateSpan()
    {
        $date = $this->getPublicationDate(false);

        $nextMonth = $date->copy()->addMonth();

        if (! $nextMonth->isSameYear($date)) {
            return $date->format('F Y') . '/' . $nextMonth->format('F Y');
        }

        return $date->format('F') . '/' . $nextMonth->format('F Y');
    }

    public function getWallchart()
    {
        return null;
    }

    public function getPrecis()
    {
        return Arr::get($this->data, 'precis');
    }

    public function getCoverStory()
    {
        $search = $this->service->newSearch();
        $search
            ->setRelationIds([$this->getId()])
            ->setTagIds([Article::ENTITY_TYPE_TAG_ID, MagazineSection::TAG_ID_COVER_STORY]);

        return $this->service->run($search)->hydrate()->first();
    }

    public function getSections()
    {
        $search = $this->service->newSearch();
        $search->setRelationIds([$this->getId()]);

        $articles = $this->service->run($search)->hydrate()
            ->sortBy(function (Article $article) {
                return $article->getInfo('MagazineOrder');
            });

        $sections = [];

        $articles->each(function (Article $item) use (&$sections) {

            $tagId = 0;
            if ($section = $item->getTagGroup(MagazineSection::class)->first()) {
                $tagId = $section->getTagId();
            }

            if ($tagId != MagazineSection::TAG_ID_COVER_STORY) {
                if (! isset($sections[$tagId])) {
                    $sections[$tagId] = [];
                }
                $sections[$tagId][] = $item;
            }
        });

        $result = [];
        foreach ($sections as $tagId => $items) {
            $result[] = new Section($tagId, $items);
        }

        return $result;
    }

    public function getIssue()
    {
        return $this->getTagGroup(SupplementIssue::class)->first()->getName();
    }

    public function isSupplement()
    {
        return $this instanceof Supplement;
    }

    public function canView(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (SubscriptionLevel::usingThis(active_host())) {
            return $this->comparePermissions($user);
        }

        if ($user->isSubscriber()) {
            return true;
        }

        return false;
    }

    public function requiresPermissions(): array
    {
        return [Feature::TYPE_MAGAZINE_DOWNLOAD];
    }

    public function getDownloadUrl()
    {
        return route('magazines.download', $this->getSlug());
    }
}
