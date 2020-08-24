<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DirectorySector extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected $visible = [
        'name',
    ];
    public function directoryIndividuals()
    {
        return $this->belongsToMany(DirectoryIndividual::class, 'directory_individuals_sectors');
    }
}
