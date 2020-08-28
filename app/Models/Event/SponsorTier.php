<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;

class SponsorTier extends Model implements ImportableInterface
{
    use ImportableTrait;

    protected $guarded = ['id'];

    public static function getEntityBundle()
    {
        return 'sponsor_tier';
    }

    public function getChunkSizeAttribute()
    {
        $chunks = [
                'Platinum' => 2,
                'Gold' => 3,
                'Silver' => 4,
                'Bronze' => 6,
                'Networking' => 6,
                'Supporting organisation' => 6,
                'Knowledge partner' => 6,
                'Media partner' => 6,
                'ADHOC 1' => 6,
                'ADHOC 2' => 6,
        ];

        if (in_array($this->name, array_keys($chunks))) {
            return $chunks[$this->name];
        }

        return 6; //default
    }
}
