<?php

namespace App\Repositories;

use App\Models\Product;
use InfyOm\Generator\Common\BaseRepository;
use App\Models\Price;

/**
 * Class ProductsRepository
 * @package App\Repositories
 * @version May 14, 2018, 3:27 pm UTC
 *
 * @method Products findWithoutFail($id, $columns = ['*'])
 * @method Products find($id, $columns = ['*'])
 * @method Products first($columns = ['*'])
 */
class ProductRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'description',
        'users',
        'type'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Product::class;
    }

    public function updateRelations($model, $attributes)
    {
        $config = config('currencies.list');
        if (isset($attributes['prices'])) {
            foreach ($attributes['prices'] as $currency => $priceItem) {
                if (isset($config[$currency])) {
                    $price = $model->prices->where('currency', $currency)->first();
                    if (! $price) {
                        $price = new Price();
                        $price->currency = $currency;
                        $price->priceable_id = $model->id;
                        $price->priceable_type = get_class($model);
                    }
                    $price->price = $priceItem['price'];

                    $price->save();
                }
            }

            unset($attributes['prices']);
        }
        return parent::updateRelations($model, $attributes);
    }
    protected function fixattribues(array $attributes)
    {
        foreach (Product::SUPPORTED_FEATURES as $feature) {
            if (! isset($attributes['feature_' . $feature])) {
                $attributes['feature_' . $feature] = 0;
            }
        }
        return $attributes;
    }

    public function create(array $attributes)
    {
        return parent::create($this->fixattribues($attributes));
    }

    public function update(array $attributes, $id)
    {
        return parent::update($this->fixattribues($attributes), $id);
    }
}
