<?php

namespace App\Models;

use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use Illuminate\Database\Eloquent\Model;

class ArticleType extends Model implements ImportableInterface
{
    use ImportableTrait;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @return void
     */
    public static function boot()
    {
        static::deleting(function ($articleType) {
            $articleType->articles()->detach();
        });
    }

    /**
     * @return string
     */
    public static function getEntityBundle()
    {
        return 'article_type';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    /**
     * @param        $query
     * @param string $subject
     */
    public function scopeAnalysisSubject($query, string $subject)
    {
        $query->where('name', 'analysis: ' . $subject);
    }

    /**
     * @param        $query
     * @param string $name
     */
    public function scopeNameIs($query, string $name)
    {
        $query->where('name', $name);
    }

    /**
     * @param       $query
     * @param array $names
     */
    public function scopeNameOneOf($query, array $names)
    {
        $query->whereIn('name', $names);
    }
}
