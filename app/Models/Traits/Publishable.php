<?php

namespace App\Models\Traits;

use DateTime;
use Illuminate\Database\Eloquent\Builder;

trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope('is_published', function (Builder $builder) {
            if (! auth()->user() || (auth()->user()->admin == false || ! request()->session()->get('previewing'))) {
                $builder->whereNotNull('published_at');
                $builder->where('published_at', '<', \DB::raw('now()'));
            }
        });
    }

    /* Scopes */

    public function scopeLatest($query, $column = null)
    {
        if (! $column) {
            return $query->orderByDesc('published_at')->orderByDesc('created_at');
        }

        return $query->orderByDesc($column);
    }

    /* Helpers */

    public function isPublished()
    {
        if ($this->published_at === null) {
            return false;
        }

        if ($this->published_at->isPast()) {
            return true;
        }

        return false;
    }

    public function publish(DateTime $published_at = null)
    {
        if (! $published_at) {
            $published_at = new DateTime();
        }

        $this->published_at = $published_at;
        $this->save();
    }

    public function unpublish()
    {
        $this->published_at = null;
        $this->save();
    }

    /**
     * @param string      $field_article_date
     * @param bool|string $status
     * @throws \Exception
     */
    protected function fillPublishedAt($field_article_date, $status)
    {
        $published_at = new DateTime($field_article_date);

        $this->published_at = $status && $published_at > new DateTime('1970-01-01 00:00:01')
            ? new DateTime($field_article_date) : null;
    }
}
