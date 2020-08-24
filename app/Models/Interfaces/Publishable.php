<?php

namespace App\Models\Interfaces;

use DateTime;
use Illuminate\Database\Eloquent\Builder;

interface Publishable
{
    public function isPublished();
    public function publish(DateTime $published_at = null);
    public function unpublish();
}
