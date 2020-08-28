<?php

namespace App\Models;

use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Interfaces\Routable as RoutableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Traits\Routable as RoutableTrait;
use DateTime;
use Facades\App\Classes\Drupal;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Helper\ProgressBar;
use Cache;

class Sector extends Model implements ImportableInterface, RoutableInterface
{
    use ImportableTrait;
    use RoutableTrait;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static function getEntityBundle()
    {
        return 's';
    }

    public function toResponse($request)
    {
        $brand = resolve(Brand::class);
        $values = Cache::remember('sector_' . $brand->id . '_' . $this->id, 3, function () {
            $site_lead = $this->articles()->with('media')->stickyFirst()->latest('published_at')->isSiteLead()->since('1 year ago')->limit(3)->get();
            $latest = $this->articles()->with('media')->latest('published_at')->since('1 year ago')->exclude($site_lead)->limit(4)->get();
            $news = $this->articles()->with('media')->latest('published_at')->ofType('news')->since('3 years ago')->exclude($site_lead, $latest)->limit(4)->get();
            $analysis = $this->articles()->with('media')->latest('published_at')->ofType('analysis')->since('3 years ago')->exclude($site_lead, $latest, $news)->limit(4)->get();
            $research = $this->articles()->with('media')->latest('published_at')->ofType('research')->since('3 years ago')->exclude($site_lead, $latest, $news, $analysis)->limit(4)->get();
            $insight = $this->articles()->with('media')->latest('published_at')->ofType('insight')->since('3 years ago')->exclude($site_lead, $latest, $news, $analysis, $research)->limit(4)->get();
            $insights = $this->articles()->with('media')->latest('published_at')->magazineSectionIs('insights')->since('3 years ago')->exclude($site_lead, $latest, $news, $analysis, $research)->limit(4)->get();
            $trending = $this->articles()->with('media')->mostPopular()->since('3 years ago')->limit(5)->get();

            $article_types = ArticleType::nameOneOf(['News', 'Analysis', 'Research', 'Insight'])->pluck('id', 'name');

            $view_all_links = [
                'news' => '/search?search=&order=date_desc&article_types[]=' . $article_types['News'] . '&sectors[]=' . $this->id,
                'analysis' => '/search?search=&order=date_desc&article_types[]=' . $article_types['Analysis'] . '&sectors[]=' . $this->id,
                'research' => '/search?search=&order=date_desc&article_types[]=' . $article_types['Research'] . '&sectors[]=' . $this->id ,
                'insight' => '/search?search=&order=date_desc&article_types[]=' . $article_types['Insight'] . '&sectors[]=' . $this->id,
                'latest' => '/search?search=&order=date_descdate_desc&sectors[]=' . $this->id,
            ];

            return [
                'active_sector' => $this,
                'sector' => $this,
                'site_lead' => $site_lead,
                'latest' => $latest,
                'news' => $news,
                'analysis' => $analysis,
                'research' => $research,
                'insight' => $insight,
                'insights' => $insights,
                'trending' => $trending,
                'view_all_links' => $view_all_links,
            ];
        });
        return view('sectors.show', $values);
    }

    /* Relations */

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
