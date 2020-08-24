<?php

namespace App\Http\ViewComponents;

use App\Classes\LayoutBuilder;
use App\Http\ViewComponents\ComponentLogic\CachedComponentLogic;
use App\Http\ViewComponents\ComponentLogic\ComponentLogic;
use App\Http\ViewComponents\ComponentLogic\DefaultComponentLogic;
use App\Http\ViewComponents\Exceptions\PageBlockException;
use App\Http\ViewComponents\Exceptions\PageBlockSkipRender;
use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class PageBlockBase implements PageBlock
{
    /**
     * @var LayoutBuilder
     */
    protected $layout;

    /**
     * @var array
     */
    protected $blockConfig;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var ComponentLogic
     */
    protected $logicClass;

    /**
     * @param LayoutBuilder $layout
     * @param array $blockConfig
     * @param array $args
     */
    public function __construct(LayoutBuilder $layout, array $blockConfig, array $args = [])
    {
        $this->layout = $layout;
        $this->blockConfig = $blockConfig;
        $this->attributes = $blockConfig['attributes'] ?? [];
        $this->logicClass = $this->getLogicClass($blockConfig['logic'] ?? '');
        $this->args = $args;
    }

    /**
     * @return mixed
     * @throws Exceptions\PageBlockSkipRender
     */
    public function loadData()
    {
        $this->shouldRender();

        $logicClass = $this->logicClass;
        if ($this->shouldCache()) {
            $logicClass = new CachedComponentLogic($logicClass, $this->attributes['cache_ttl']);
        }

        return $logicClass->loadData($this->layout->getPageConfig(), $this->blockConfig);
    }

    /**
     * @return bool|void
     * @throws PageBlockSkipRender
     */
    public function shouldRender()
    {
        if (! ($this->blockConfig['enabled']) ?? false) {
            throw new PageBlockSkipRender();
        }
    }

    private function shouldCache()
    {
        if (isset($this->attributes['cache_ttl'])) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        try {
            return view($this->view(), $this->viewData());
        } catch (PageBlockWithEmptySource $exception) {
            Log::info('Page Block is empty: ' . $this->key);
        } catch (PageBlockException $exception) {
        }

        return '';
    }

    /**
     * @return string
     */
    protected function blockName()
    {
        // @todo: Remove this for new version compatibility
        return static::SLUG . ($this->args['postfix'] ?? '');
    }

    /**
     * @param string $logicKey
     * @return ComponentLogic|null
     */
    public function getLogicClass(string $logicKey)
    {
        $className = DefaultComponentLogic::class;
        if ($logicKey) {
            $logicClass = Str::before(Str::before($logicKey, '@'), '[');
            $className = "\\App\\Http\\ViewComponents\\ComponentLogic\\{$logicClass}";
        }

        return app($className, ['pageConfig' => $this->layout->getPageConfig()]);
    }
}
