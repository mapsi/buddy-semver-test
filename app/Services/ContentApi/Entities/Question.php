<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Entities;

class Question extends DataEntity
{
    const ENTITY_TYPE_TAG_ID = 5556;

    public function getOrder(): int
    {
        return $this->data['order'];
    }

    /**
     * Retrieve the answer for the specific jurisdiction
     * @param  string $id The id of the jurisdiction
     * @return string the html text for the jurisdiction answer
     */
    public function getAnswer(string $id): string
    {
        return $this->getInfo('Answers')[$id] ?? "";
    }

    public function setIndex(int $index)
    {
        $this->data['index'] = $index;
    }

    public function getIndex()
    {
        return $this->data['index'];
    }

    public function getView()
    {
        return '';
    }
}
