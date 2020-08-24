<?php

namespace App\Http\ViewComponents\ComponentLogic;

use App\Services\ContentApi\Search;
use Exception;
use Illuminate\Support\Facades\Cache;

class DefaultComponentLogic extends BaseComponentLogic
{
    protected $contentApiService = null;

    public function __construct(array $pageConfig)
    {
        parent::__construct($pageConfig);
        $this->contentApiService = brandService();
    }

    public function loadData(array $pageConfig, array $blockConfig)
    {
        $machine_name = get_host_config('machine_name');

        $queryAttrs = ($blockConfig['attributes'] ? ($blockConfig['attributes']['query'] ?? []) : []) ?? [];

        $request = request();

        $extractable = $queryAttrs;
        if (is_callable($queryAttrs)) {
            $extractable = $queryAttrs();
        }
        extract($extractable);

        if (! isset($startPage)) {
            $startPage = 1;
        }

        $tagIds = is_array($tagId) ? array_values($tagId) : [$tagId];

        if (isset($excludeTagIds)) {
            $excludeTagIds = is_array($excludeTagIds) ? array_values($excludeTagIds) : [$excludeTagIds];
        }

        if (! empty($unionTagIds)) {
            $unitedTagIds = [];

            foreach ($tagIds as $tagId) {
                foreach ($unionTagIds as $unitedTagId) {
                    $unitedTagIds[] = [$tagId, $unitedTagId];
                }
            }

            if (count($unitedTagIds) > 0) {
                $tagIds = $unitedTagIds;
            } else {
                // we didn't get any topic ids but we got entity ids, only use entity ids tags
                $tagIds = $unionTagIds;
            }
        }

        // exclude content fetched by other blocks
        // increase page size by number of items included in other blocks
        if (isset($excludeBlocks) && is_array($excludeBlocks)) {
            $excludedBlockItems = [];
            foreach ($excludeBlocks as $excludeBlock) {
                $excludeBlockName = $machine_name . '_' . $excludeBlock;
                $cachedData = Cache::get($excludeBlockName);

                if ($cachedData) {
                    foreach ($cachedData as $item) {
                        $excludedBlockItems[] = $item;
                    }
                }
            }
            $increasedPageSize = $pageSize + count($excludedBlockItems);
        }

        $search = $this->contentApiService->newSearch();
        $search->setTagIds($tagIds);
        $search->setPageSize($pageSize);
        if (isset($increasedPageSize)) {
            $search->setPageSize($increasedPageSize);
        }
        if (isset($excludeTagIds)) {
            $search->excludeTagIds($excludeTagIds);
        }
        $search->setStartPage((int) $request->get('page', $startPage));
        $search->setSort($sort ?? Search::SORT_TYPE_LATEST);
        if (isset($title)) {
            $search->setTitle($title);
        }

        $result = $this->contentApiService->run($search);

        if (isset($exclude)) {
            if (! is_array($exclude)) {
                $exclude = [$exclude];
            }
            $exclude = collect($exclude);
            $result = $result->exclude($exclude);
        }

        // if content of other blocks to be excluded: exclude and apply original page size
        // TODO: optimise
        if (isset($excludedBlockItems)) {
            $excludedBlockItems = collect($excludedBlockItems);
            $result = $result->exclude($excludedBlockItems)->limit($pageSize);
        }

        $currentBlock = array_filter($pageConfig['blocks'], function ($item) use ($blockConfig) {
            if ($item === $blockConfig) {
                return key($item);
            }
        });

        if (is_array($currentBlock) && (count($currentBlock) == 1)) {
            $blockName = $machine_name . '_' . array_keys($currentBlock)[0];
            Cache::put($blockName, $result->hydrate(), 180);
        }

        return $result;
    }
}
