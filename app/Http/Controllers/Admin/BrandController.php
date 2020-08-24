<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Brand;

class BrandController extends ResourcefulController
{
    protected static $model = Brand::class;
    protected $allowedFiltersArray = [
        'machine_name',
    ];
    protected $allowedSortsArray = [
        'machine_name',
    ];

    public function __construct()
    {
        $this->authorizeResource(self::$model);
    }

    public function show(Brand $model)
    {
        return view($this->view(), compact('model'));
    }
}
