<?php

namespace App\Http\ViewComponents\ComponentLogic;

use App\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class BaseComponentLogic implements ComponentLogic
{
    /**
     * @var array
     */
    protected $pageConfig;

    /**
     * @var string
     */
    protected $brandSlug;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param array $pageConfig
     */
    public function __construct(array $pageConfig)
    {
        $this->pageConfig = $pageConfig;
        $this->brandSlug = resolve(Brand::class)->machine_name;
    }

    /**
     * @param array $pageConfig
     * @param array $blockConfig
     * @return array|mixed
     */
    public function loadData(array $pageConfig, array $blockConfig)
    {
        $method = $this->getMethodFromLogic($blockConfig['logic']);
        $key = $this->getDataKeyFromLogic($blockConfig['logic']);
        $this->attributes = $blockConfig['attributes'] ?? [];

        return $key
            ? $this->$method()[$key]
            : $this->$method();
    }

    /**
     * @param $logic
     * @return string
     */
    protected function getMethodFromLogic($logic)
    {
        return Str::before(Str::after($logic, '@'), '[');
    }

    /**
     * @param $logic
     * @return string
     */
    protected function getDataKeyFromLogic($logic)
    {
        return Str::contains($logic, '[')
            ? Str::before(Str::after($logic, '['), ']')
            : false;
    }

    /**
     * @param      $key
     * @param null $default
     * @return mixed
     */
    public function attribute($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }
}
