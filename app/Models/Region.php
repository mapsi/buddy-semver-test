<?php

namespace App\Models;

use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use DateTime;
use Facades\App\Classes\Drupal;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Symfony\Component\Console\Helper\ProgressBar;

class Region extends Model implements ImportableInterface
{
    use HasSlug;
    use ImportableTrait;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static function getEntityBundle()
    {
        return 'region_and_countries';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /* Relations */

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }
}
