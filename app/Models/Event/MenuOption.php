<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;

class MenuOption extends Model implements ImportableInterface
{
    use ImportableTrait;

    protected $guarded           = ['id'];
    public static function getEntityBundle()
    {
        return 'banner';
    }
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
