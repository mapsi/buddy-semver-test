<?php

namespace App\Models\Interfaces;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Routable extends Responsable
{
    public function routes(): MorphMany;
}
