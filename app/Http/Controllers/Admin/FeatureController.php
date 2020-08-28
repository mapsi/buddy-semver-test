<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Feature;

class FeatureController extends ResourcefulController
{
    protected static $model = Feature::class;

    public function __construct()
    {
        $this->authorizeResource(self::$model);
    }

    public function show(Feature $model)
    {
        return view($this->view(), compact('model'));
    }
}
