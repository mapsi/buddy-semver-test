<?php

namespace App\Models\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Brandable
{
    public function brand(): BelongsTo;
}
