<?php

namespace App\Models\Event\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\Event\SponsorTier;
use App\Models\Event\Sponsor;

class TieredSponsor extends Pivot
{
    public $table = 'event_sponsor';
    public function tier()
    {
        return $this->belongsTo(SponsorTier::class);
    }
    public function scopeTiered($query)
    {
        $query->whereNotNull('tier_id');
    }
    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
}
