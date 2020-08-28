<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Entities\Author;
use App\Services\ContentApi\Entities\Firm;
use App\Services\ContentApi\Entities\QAJurisdiction;
use App\Services\ContentApi\Entities\Question;
use App\Services\ContentApi\TagGroups\Brand;
use Illuminate\Support\Collection;
use App\Services\ContentApi\Traits\HasAuthors;
use App\Services\ContentApi\Traits\HasFirms;
use App\Services\ContentApi\Traits\HasArticles;
use App\Services\ContentApi\Traits\HasFirmProfiles;
use App\Services\ContentApi\Search;

class QATopic extends DataEntity
{
    use HasArticles;
    use HasFirms;
    use HasAuthors;
    use HasFirmProfiles;

    const ENTITY_TYPE_TAG_ID = 16704;
    public function getBody()
    {
        return json_decode(parent::getBody());
    }

    /**
     * It's necessary to retrieve jurisdictions from content API to access contributor information
     * @return Illuminate\Support\Collection collection of jurisdictions
     */
    public function getJurisdictions(): Collection
    {
        $search = $this->service->newSearch();
        $search->setTagIds([Brand::TAG_ID_KNOWHOWGCR, QAJurisdiction::ENTITY_TYPE_TAG_ID]);
        $search->setSort(Search::SORT_TYPE_TITLE);
        $search->setRelationIds([$this->getId()]);
        return $this->service->run($search)->hydrate(null, QAJurisdiction::class);
    }

    public function getTopicLayout(): Collection
    {
        $jsonLayout = $this->getBody();
        if (count($jsonLayout) != 1) {
            return collect();
        }

        $questions = $this->getQuestions();
        $layout = collect();
        $questionIndex = 1;
        foreach ($jsonLayout[0]->Children as $subTopic) {
            $questionsGroup = collect();

            foreach ($subTopic->Questions as $layoutQuestion) {
                $question = $questions->first(function ($question) use ($layoutQuestion) {
                    return $layoutQuestion->Id === $question->getOriginalId();
                });

                if (! $question) {
                    continue;
                }

                $question->setIndex($questionIndex);
                $questionsGroup->push($question);
                $questionIndex++;
            }

            $subTopicData = collect();
            $subTopicData->put('title', $subTopic->Name);
            $subTopicData->put('id', $subTopic->Id);
            $subTopicData->put('questions', $questionsGroup->values());
            $layout->push($subTopicData);
        }

        return $layout;
    }

    private function getQuestions()
    {
        $search = $this->service->newSearch();
        $search->setTagIds([Question::ENTITY_TYPE_TAG_ID, Brand::TAG_ID_KNOWHOWGCR]);
        $search->setRelationIds([$this->getId()]);
        return $this->service->run($search)->hydrate(null, Question::class);
    }

    public function getView()
    {
        return '';
    }
}
