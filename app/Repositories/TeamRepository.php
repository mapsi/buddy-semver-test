<?php

namespace App\Repositories;

use App\Models\Team;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class TeamRepository
 * @package App\Repositories
 * @version May 16, 2018, 1:56 pm UTC
 *
 * @method Team findWithoutFail($id, $columns = ['*'])
 * @method Team find($id, $columns = ['*'])
 * @method Team first($columns = ['*'])
*/
class TeamRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'id' => 'like',
        'firm_ref' => 'like',
        'name' => 'like',
        'vat' => 'like',
        'members.email' => 'like',
        'members.name' => 'like',
        'members.friendly_id' => 'like',
        'company' => 'like',
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Team::class;
    }

    public function where($field, $condition, $value)
    {
        $this->applyConditions([[$field,$condition,$value]]);
    }
    /**
     * Check if entity has relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function hasCount($relation, $operator = '>=', $count = 1)
    {
        $this->model = $this->model->has($relation, $operator, $count);

        return $this;
    }
}
