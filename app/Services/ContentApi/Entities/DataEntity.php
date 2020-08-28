<?php

namespace App\Services\ContentApi\Entities;

use Carbon\Carbon;
use App\Models\Brand;
use App\Models\User;
use App\Services\ContentApi\Interfaces\RequiresPermissions;
use App\Services\ContentApi\Results;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\EntityType;
use App\Services\ContentApi\TagGroups\LexologySearchableType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

abstract class DataEntity implements Arrayable, Jsonable, RequiresPermissions
{
    const TAG_ID_SOURCED_FROM_DRUPAL = 85154;

        // GDR TEMPLATE VARIANTS
        const IMAGE_VARIANTS_LG = 'lg';
        const IMAGE_VARIANTS_MD = 'md';
        const IMAGE_VARIANTS_SM = 'sm';
        const IMAGE_VARIANTS_AVATAR = 'avatar';
        const IMAGE_VARIANTS_THUMB = 'thumb';
        const IMAGE_VARIANTS_FULL_GRID = 'full-grid';
        const IMAGE_VARIANTS_HALF_GRID = 'half-grid';
        const IMAGE_VARIANTS_PORTRAIT = 'portrait';

        // WTR/IAM TEMPLATE VARIANTS
        const IMAGE_VARIANTS_BANNER = 'banner';
        const IMAGE_VARIANTS_THUMBNAIL = 'thumbnail';
        const IMAGE_VARIANTS_LETTERBOX = 'letterbox';
        const IMAGE_VARIANTS_COVER = 'cover';

    public static $imageVariants = [
            self::IMAGE_VARIANTS_LG => [945, 526],
            self::IMAGE_VARIANTS_MD => [616, 347],
            self::IMAGE_VARIANTS_SM => [301, 168],
            self::IMAGE_VARIANTS_AVATAR => [231, 279],
            self::IMAGE_VARIANTS_THUMB => [96, 96],
            self::IMAGE_VARIANTS_FULL_GRID => [1248, 235],
            self::IMAGE_VARIANTS_HALF_GRID => [615, 210],
            self::IMAGE_VARIANTS_PORTRAIT => [395, 477],
            self::IMAGE_VARIANTS_BANNER => [750, 450],
            self::IMAGE_VARIANTS_THUMBNAIL => [190, 190],
            self::IMAGE_VARIANTS_LETTERBOX => [355, 120],
            self::IMAGE_VARIANTS_COVER => [300, 389]
        ];

    protected $service;
    protected $data;

    public function __construct(array $data, Service $service = null)
    {
        $this->service = $service ?? brandService();
        $this->data = $data;
    }

    public function getSourceId()
    {
        return $this->data['sourceId'];
    }

    public function getOriginalId()
    {
        return $this->data['originalId'];
    }

    public function getTitle()
    {
        return $this->data['title'];
    }

    public function getEntityType()
    {
        return class_basename($this);
    }

    public function getCanonicalUrl()
    {
        $url = $this->data['sourceLink'] ?? '';
        $pos = strpos($url, "://");
        $pos = false === $pos ? 0 : $pos + 3;

        return substr($url, strpos($url, '/', $pos));
    }

    public function getMediaUrl(string $variant = null)
    {
        if (empty($this->data['imageLink'])) {
            if (! get_host_config('default_images')) {
                return '';
            }

            if ($variant && $variant !== 'lg') {
                $default = asset('images/' . resolve(Brand::class)->machine_name . '-default-' . $variant . '.jpg');
                return $default;
            }

            return '';
        }


        if ($variant) {
            $imagepath = strtok($this->data['imageLink'], '?');

            // DOCUMENTATION
            // https://globelbr.atlassian.net/wiki/spaces/GxR/pages/467795969/Serverless+Image+Handler+images.lbr.cloud

            // TODO
            // - List of allowed domains/buckets so we can use for testing in future

            if (substr($imagepath, 0, 24) == 'https://files.lbr.cloud/') {
                $path = str_replace('https://files.lbr.cloud/', '', $imagepath);

                $width = self::$imageVariants[$variant][0];
                $height = self::$imageVariants[$variant][1];

                $config = '{
                    "bucket": "files.lbr.cloud",
                    "key": "' . $path . '",
                    "edits": {
                      "resize": {
                        "width": ' . $width . ',
                        "height": ' . $height . ',
                        "fit": "cover"
                      }
                    }
                }';

                return "https://images.lbr.cloud/v1/" . base64_encode($config);
            }
        }

        return $this->data['imageLink'];
    }

    public function getBody()
    {
        return Arr::get($this->data, 'body');
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        return $this->getData();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getSlug()
    {
        return $this->data['slug'];
    }

    public function getPublicationDate($format = null)
    {
        return $this->formatConditionally('publishedFrom', $format);
    }

    protected function formatConditionally($key, $format)
    {
        if (! $data = Arr::get($this->data, $key)) {
            return null;
        };

        $date = Carbon::parse($data);
        if ($format === false) {
            return $date;
        }

        $dateFormat = is_null($format) ? 'd F Y' : $format;

        return $date->format($dateFormat);
    }

    public function getLastUpdateDate($format = null)
    {
        return $this->formatConditionally('lastUpdated', $format);
    }

    public function getTagGroup(string $tagGroup): Collection
    {
        return collect($this->filterByTagGroupId($tagGroup::TAG_GROUP_ID))->mapInto($tagGroup);
    }

    protected function filterByTagGroupId(int $tagGroupId)
    {
        return Arr::where($this->data['tags'], function ($tag) use ($tagGroupId) {
            return $tag['typeId'] === $tagGroupId;
        });
    }

    abstract public function getView();

    public function getInfo($key = null)
    {
        if (! isset($this->data['customInfo'])) {
            return null;
        }

        $customInfo = $this->data['customInfo'];

        if (is_null($key)) {
            return $customInfo;
        }

        if (isset($customInfo[$key])) {
            return $customInfo[$key];
        }

        return null;
    }

    protected function refresh()
    {
        $this->data = $this->service->getContent($this->getId())->getData();

        return $this;
    }

    public function getId()
    {
        return $this->data['id'];
    }

    public function getRelations($dataEntity = null): Collection
    {
        $relations = $this->data['relations'];

        if (count($relations) === 0) {
            $this->refresh();
            $relations = $this->data['relations'];
        }

        if (! is_array(current($relations))) {
            $this->refresh();
            $relations = $this->data['relations'];
        }
        $relations = collect($this->data['relations']);

        if (! is_null($dataEntity)) {
            $relations = $relations->filter(function ($item) use ($dataEntity) {
                return array_filter($item['tags'], function ($tag) use ($dataEntity) {
                    if (! is_array($tag)) {
                        return null;
                    }
                    return ($tag['typeId'] === EntityType::TAG_GROUP_ID)
                        && ($tag['tagId'] === $dataEntity::ENTITY_TYPE_TAG_ID);
                });
            });
        }
        $relations = $relations->map(function ($item) use ($dataEntity) {
            $class = Results::getPresentableType($item['tags'], $dataEntity);
            if ($class) {
                return new $class($item);
            }
        })->filter();


        return $relations;
    }

    public function isLexology()
    {
        return Arr::first($this->data['tags'], function ($tag) {
            return $tag['tagId'] === LexologySearchableType::TAG_ID_LEXOLOGY_ARTICLE;
        });
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function __sleep()
    {
        return ['data'];
    }

    public function __wakeup()
    {
        $this->service = brandService();
    }

    public function requiresPermissions(): array
    {
        return [];
    }
}
