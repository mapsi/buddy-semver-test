<?php

namespace App\Providers;

use App\Classes\BotDetect;
use App\Http\ViewComposers\DFPComposer;
use App\Http\ViewComposers\DirectorySearchBlockComposer;
use App\Http\ViewComposers\DownloadLatestMagazineComposer;
use App\Http\ViewComposers\EventsLanguageDropDownComposer;
use App\Http\ViewComposers\EventsMenuComposer;
use App\Http\ViewComposers\EventsPreviousYearGalleryComposer;
use App\Http\ViewComposers\EventsProgrammeComposer;
use App\Http\ViewComposers\FeaturedFirmComposer;
use App\Http\ViewComposers\FeaturedLawyerComposer;
use App\Http\ViewComposers\FeaturedLawyersComposer;
use App\Http\ViewComposers\FeaturedRankingComposer;
use App\Http\ViewComposers\FindExpertComposer;
use App\Http\ViewComposers\GoogleBotComposer;
use App\Http\ViewComposers\JiraIssueCollectorComposer;
use App\Http\ViewComposers\LayoutComposer;
use App\Http\ViewComposers\SeriesComposer;
use App\Http\ViewComposers\ShareThisComposer;
use App\Http\ViewComposers\SideMenuComposer;
use App\Http\ViewComposers\TwitterComposer;
use App\Models\Brand;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use View;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Brand $brand = null)
    {
        $botdetect = new BotDetect(Request());
        View::composer('*', function ($view) use ($brand, $botdetect) {
            if (! array_key_exists('brand', $view->getData())) {
                $view->with('brand', $brand);
            }
            $view->with('botdetect', $botdetect);
        });

        View::composer('*', function ($view) {
            $view->with('theme', env('ABTA_THEME', 'admin'));
        });

        View::composer('auth.loginwall', function ($view) {
            Request::session()->put('url.intended', Request()->getPathInfo());
        });
        View::composer('_partials.twitter', TwitterComposer::class);
        View::composer('directories._partials.search_block', DirectorySearchBlockComposer::class);
        View::composer('directories._partials.side_menu', SideMenuComposer::class);
        View::composer('directories._partials.find_expert', FindExpertComposer::class);
        View::composer('directories._partials.featured_firm', FeaturedFirmComposer::class);
        View::composer('directories._partials.featured_lawyer', FeaturedLawyerComposer::class);
        View::composer('directories._partials.featured_lawyers', FeaturedLawyersComposer::class);
        View::composer('directories._partials.featured_ranking', FeaturedRankingComposer::class);
        View::composer('_partials.share_this', ShareThisComposer::class);
        View::composer('_partials.adunits.adunit_header_scripts', DFPComposer::class);
        View::composer('_partials.articles.google-bot', GoogleBotComposer::class);
        View::composer('_layouts.events', EventsMenuComposer::class);
        View::composer('events.*', EventsMenuComposer::class);
        View::composer('_layouts.events', EventsLanguageDropDownComposer::class);
        View::composer('events.programme', EventsProgrammeComposer::class);
        View::composer('events.about.previous-year', EventsPreviousYearGalleryComposer::class);
        View::composer(['admin._layouts.base', '_layouts/base'], JiraIssueCollectorComposer::class);
        View::composer(LayoutComposer::VIEWS, LayoutComposer::class);
        View::composer([
            '_layouts.components.survey-links',
            '_partials/survey_list_sidebar',
            '_layouts.components.insight-links',
        ], SeriesComposer::class);
        View::composer('_partials.survey_download_magazine_pdf', DownloadLatestMagazineComposer::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
