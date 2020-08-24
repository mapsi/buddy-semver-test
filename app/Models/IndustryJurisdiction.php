<?php

namespace App\Models;

use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class IndustryJurisdiction extends Model implements ImportableInterface
{
    use ImportableTrait;

    public $timestamps = false;

    protected $fillable = ['name'];

    protected $visible = ['name'];

    public static function getEntityBundle()
    {
        return 'industry_jurisdiction';
    }

    /* Overrides */

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* Helpers */

    /**
     * The active firm is the firm with the latest article.
     */
    public function getActiveFirm()
    {
        $latestArticle = $this->firms
            ->flatMap(function ($firm) {
                return $firm->articles;
            })
            ->sortByDesc('published_at')
            ->first();

        if (! $latestArticle) {
            return null;
        }

        return $this->firms->firstWhere('id', $latestArticle->firm_id);
    }

    /**
     * The latest articles are the three latest articles from each industry jurisdiction's active
     * firm.
     */
    public function getLatestArticles()
    {
        $latestFirm = $this->getActiveFirm();

        return $latestFirm->articles->sortByDesc('published_at')->slice(0, 3);
    }

    /**
     * The latest articles from the active firm.
     */
    public function getActiveArticles()
    {
        $latestFirm = $this->getActiveFirm();

        return $latestFirm->articles->sortByDesc('published_at');
    }

    /**
     * The latest articles not from the active firm.
     *
     * @return Collection
     */
    public function getArchivedFirms()
    {
        $latestFirm = $this->getActiveFirm();

        return $this->firms()
            ->beingShownOnContributorsOfBrand(active_host())
            ->get()
            ->reject(function ($firm) use ($latestFirm) {
                return $firm->id == $latestFirm->id;
            })
            ->each(function ($firm) {
                $firm->articles->sortByDesc('published_at');
            });
    }

    public static function getContributors()
    {
        return static::whereHas('firms')->with('firms.articles')->get();
    }

    public static function getContributorsSortedByIpFirst()
    {
        return static::getContributors()->sortBy(function ($contributor) {
            if ($contributor->name[0] == 'I' && $contributor->name[1] == 'P') {
                return 1 . $contributor->name;
            } else {
                return 2 . $contributor->name;
            }
        });
    }
}
