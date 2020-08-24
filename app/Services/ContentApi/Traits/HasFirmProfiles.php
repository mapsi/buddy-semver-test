<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use App\Services\ContentApi\Entities\DataEntity;

trait HasFirmProfiles
{
    public function getFirmProfiles(): Collection
    {
        $firms = $this->getFirms();
        $firmProfiles = $firms->filter(function ($firm) {
            return Arr::first($firm->data['tags'], function ($tag) {
                    return $tag['tagId'] == DataEntity::TAG_ID_SOURCED_FROM_DRUPAL;
            });
        })->map(function ($firm) {
            return $this->service->fetchFirmProfileByFirmId($firm->getId());
        });

        return (! empty($firmProfiles)) ? $firmProfiles : $firms;
    }
}
