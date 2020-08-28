<?php

namespace App\Repositories;

use App\Models\Subscription;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class SubscriptionRepository
 * @package App\Repositories
 * @version May 16, 2018, 11:40 am UTC
 *
 * @method Subscription findWithoutFail($id, $columns = ['*'])
 * @method Subscription find($id, $columns = ['*'])
 * @method Subscription first($columns = ['*'])
*/
class SubscriptionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'team_id',
        'parent_id',
        'currency',
        // no way to use whereHasMorph() in L5 repository criteria which is needed for the polymorphic relationship
        // 'subscribable.name' => 'like',
        'payment_details',
        'payment_id' => 'like',
        'payment_provider',
        'users' => '=',
        'type',
        'team.name' => 'like',
        'lead_id' => '='
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Subscription::class;
    }

    public function where($field, $condition, $value)
    {
        $this->applyConditions([[$field,$condition,$value]]);
    }
    public function update(array $attributes, $id)
    {

        if (isset($attributes['payment_details']) && ! json_decode($attributes['payment_details'])) {
            $attributes['payment_details'] = json_encode(['reason' => $attributes['payment_details']]);
        }
        return parent::update($attributes, $id);
    }
    public function create(array $attributes)
    {
        if (isset($attributes['payment_details']) && ! json_decode($attributes['payment_details'])) {
            $attributes['payment_details'] = json_encode(['reason' => $attributes['payment_details']]);
        }
        return parent::create($attributes);
    }
    public function join($table, $left, $right)
    {
        $this->model = $this->model->join($table, $left, $right);

        return $this;
    }
}
