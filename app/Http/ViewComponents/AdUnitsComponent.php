<?php

namespace App\Http\ViewComponents;

use Illuminate\Contracts\Support\Htmlable;

class AdUnitsComponent implements Htmlable
{
    protected $partials = [
        'inline_rectangle' => '%s__InlineRectangle_RightColumn_300x250',
        'small_rectangle' => '%s__SmallRectangle_RightColumn',
        'half_page' => '%s__HalfPageAd_RightColumn_300x600',
    ];

    /**
     * @var string
     */
    protected $activeHost;

    /*
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $folderPath;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
        $this->activeHost = active_host();
        $this->folderPath = config('partials.folder_path', '_partials/adunits/');
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function toHtml()
    {
        $partial = $this->folderPath . sprintf($this->partials[$this->type], strtoupper($this->activeHost));
        return view($partial)->render();
    }
}
