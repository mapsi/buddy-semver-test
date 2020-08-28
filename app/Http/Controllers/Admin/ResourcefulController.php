<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

abstract class ResourcefulController extends AppBaseController
{
    protected static $model = Model::class;
    protected $allowedFiltersArray = ['name'];
    protected $allowedSortsArray = ['name'];
    protected $allowedIncludesArray = [];

    public function __construct()
    {
        $this->authorizeResource(static::$model);
    }

    protected function allowedFilters(): array
    {
        return $this->allowedFiltersArray;
    }

    protected function allowedSorts(): array
    {
        return $this->allowedSortsArray;
    }

    protected function allowedIncludes(): array
    {
        return $this->allowedIncludesArray;
    }

    protected function resource(): string
    {
        return static::$model;
    }

    protected function view(): string
    {
        return strtolower(Str::plural(class_basename($this->resource())));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $result = QueryBuilder::for($this->resource(), $request)
                ->allowedFilters($this->allowedFilters())
                ->allowedSorts($this->allowedSorts())
                ->allowedIncludes($this->allowedIncludes());

            return $result->paginate($request->get('limit', 10))
                ->appends(request()->query());
        }

        return view($this->view(), ['modelId' => null]);
    }
}
