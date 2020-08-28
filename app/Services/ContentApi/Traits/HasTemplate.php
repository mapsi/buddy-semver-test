<?php

namespace App\Services\ContentApi\Traits;

trait HasTemplate
{
    public function getTemplate(): string
    {
        return $this->getInfo('TemplateName') ?? "";
    }
}
