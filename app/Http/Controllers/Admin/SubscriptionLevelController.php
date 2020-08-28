<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\SubscriptionLevelFeaturesUpdated;
use App\Http\Requests\SaveOrganisationRequest;
use App\Models\Permission;
use App\Models\SubscriptionLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionLevelController extends ResourcefulController
{
    protected static $model = SubscriptionLevel::class;
    protected $allowedIncludesArray = [
        'brand',
        'features',
        'prices',
        'subscriptions',
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // temp hack for access from subscriptions page
            if ($request->has('q')) {
                $request->merge(['name' => $request->get('q')]);
            }

            $result = QueryBuilder::for($this->resource()::query()->where('name', '!=', 'Cookie Pro'), $request)
                ->allowedFilters($this->allowedFilters())
                ->allowedSorts($this->allowedSorts())
                ->allowedIncludes($this->allowedIncludes());

            if ($request->has('q')) {
                return $result->get()->transform(function ($item) {
                    return [
                        'id' => $item['id'],
                        'text' => $item['name'],
                    ];
                });
            }

            return $result->paginate($request->get('limit', 10))
                ->appends(request()->query());
        }

        return view($this->view(), ['modelId' => null]);
    }

    public function show(SubscriptionLevel $subscription_level, Request $request)
    {
        if ($request->ajax()) {
            return $subscription_level->load(['subscriptions', 'features', 'prices']);
        }

        return view($this->view(), ['modelId' => $subscription_level->id]);
    }

    public function store(SaveOrganisationRequest $request)
    {
        $organisation = self::$model::create($request->validated());

        $this->save($organisation, $request);
        return $organisation;
    }

    protected function save(SubscriptionLevel $model, SaveOrganisationRequest $request)
    {
        $config = config('currencies.list');
        $prices = [];
        foreach ($request->get('price', []) as $currency => $amount) {
            if (! empty($amount)) {
                if (isset($config[$currency])) {
                    $price = [
                        'currency' => $currency,
                        'price' => $amount,
                    ];

                    $prices[] = $price;
                }
            }
        }

        DB::transaction(function () use ($model, $prices) {
            $model->prices()->delete();
            if (count($prices) > 0) {
                $model->prices()->createMany($prices);
            }
        });

        if ($features = $request->get('features')) {
            $permissions = $model->permissions;
            if (count($features) && $permissions->count()) {
                $permIds = $permissions->map(function (Permission $permission) {
                    return $permission->id;
                })->sort()->values();

                if ($permIds->all() === $features) {
                    return $model;
                }
            }

            $model->syncPermissions($features);
            event(new SubscriptionLevelFeaturesUpdated($model));
        }
    }

    public function update(SubscriptionLevel $subscription_level, SaveOrganisationRequest $request)
    {
        $subscription_level->fill($request->validated());
        $subscription_level->save();

        $this->save($subscription_level, $request);

        return $subscription_level;
    }

    public function destroy(SubscriptionLevel $subscription_level)
    {
        if ($subscription_level->subscriptions->isNotEmpty()) {
            return response()->json(['message' => 'Subscription level is being used.'], 409);
        }

        $subscription_level->delete();

        return;
    }
}
