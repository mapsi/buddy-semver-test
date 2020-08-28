<?php

namespace App\Classes;

use App\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LayoutBuilder
{
    const DEFAULT_BASE_VIEW = 'base_blocks';

    /**
     * @var array
     */
    private $pageConfig;

    /**
     * @var array
     */
    private $blocks;

    /**
     * @var mixed
     */
    private $brand;

    /**
     * @var array
     */
    private $appendedBreadcrumbs = [];

    /**
     * @param string $viewName
     */
    public function __construct(string $viewName)
    {
        // Optional in the case of the admin for shared views
        $this->brand = optional(resolve(Brand::class));
        $this->pageConfig = config("hosts.{$this->brand->machine_name}.pages.{$viewName}");
        $this->blocks = Arr::get($this->pageConfig, 'blocks', []);
    }

    /**
     * @return array|mixed
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @return mixed|string
     */
    public function getPageTitle()
    {
        $title = Arr::get($this->pageConfig, 'title', get_host_config('title'));

        if (is_callable($title)) {
            $title = $title();
        }

        return $title;
    }

    /**
     * @return mixed|string
     */
    public function getLayoutTemplate()
    {
        return $this->pageConfig['layout'] ?? self::DEFAULT_BASE_VIEW;
    }

    /**
     * @param string $block
     * @return bool
     */
    public function hasBlockEnabled(string $block)
    {
        return $this->getBlocks()[$block]['enabled'] ?? false;
    }

    /**
     * @param string $block
     * @return array
     */
    public function getAttributesFromBlock(string $block)
    {
        return $this->getBlocks()[$block]['attributes'] ?? [];
    }

    /**
     * @return Brand
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @return array
     */
    public function getPageConfig()
    {
        return $this->pageConfig;
    }

    /**
     * @param array $block
     * @return string|void
     */
    public function initialiseRowIfNeeded(array $block)
    {
        if (! ($block['initialises_row'] ?? false)) {
            return;
        }
        $rowClasses = $this->appendDivClasses($block['row_extra_classes'] ?? []);

        return "<div class='row no-margin $rowClasses'>";
    }

    /**
     * @param array $block
     * @return string|void
     */
    public function closeRowIfNeeded(array $block)
    {
        if (! ($block['closes_row'] ?? false)) {
            return;
        }

        return '</div>';
    }

    /**
     * @param array $block
     * @return string|void
     */
    public function changeContainersIfNeeded(array $block)
    {
        if (! ($block['change_containers'] ?? false)) {
            return;
        }
        $containerClasses = $this->appendDivClasses($block['container_extra_classes'] ?? []);

        return "</div> </div> <div class='wrapper-container $containerClasses'> <div class='container-fluid'>";
    }

    /**
     * @return array
     */
    public function breadcrumbs()
    {
        return array_merge(
            $this->pageConfig['breadcrumbs'] ?? [],
            $this->appendedBreadcrumbs
        );
    }

    /**
     * @param $displayText
     * @param $route
     */
    public function appendBreadcrumb($displayText, $route = null)
    {
        $this->appendedBreadcrumbs[] = [
            'display_text' => $displayText,
            'route' => ! empty($route)
                ? Str::contains($route, 'http') ? $route : route($route)
                : null,
        ];
    }

    /**
     * @param array $block
     * @return string
     */
    protected function appendDivClasses($extraDivClasses)
    {
        return implode(' ', $extraDivClasses ?? []);
    }
}
