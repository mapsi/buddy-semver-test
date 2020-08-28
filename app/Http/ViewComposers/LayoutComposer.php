<?php

namespace App\Http\ViewComposers;

use App\Classes\LayoutBuilder;
use Illuminate\View\View;

class LayoutComposer
{
    const VIEWS = [
        'default',
        'author/show',
        'auth/login',
        'auth/passwords/email',
        'auth/passwords/reset',
        'authoring-organisation/show',
        'articles/show',
        'asia/show',
        'series/series-type/index',
        'series/show',
        'series/insight/know-how/index',
        'series/insight/know-how/show',
        'series/insight/know-how/report',
        'editions/show',
        'editions/show_profiles',
        'editions/gxr100',
        'editions/edition_content_pieces_region_listing',
        'editions/country_survey_show',
        'editions/handbook_show',
        'editions/edition_introduction',
        'editions/edition_show_article',
        'editions/edition_country_overview',
        'editions/edition_show_organization_profile',
        'editions/edition_show_gxr100_organization_profile',
        'editions/edition_show_organization_profile_enforcer_hub',
        'editions/edition_show_person_profile',
        'editions/edition_show_person_profile_blue',
        'editions/person_profile/show',
        'editions/edition_leadgen',
        'editions/edition_leadgen_thank_you',
        'horizons/show',
        'mandates/show',
        'gtdt/show',
        'reports-centre/show',
        'vertical-restraints/show',
        'static/author-profile',
        'static/advertising',
        'static/contact',
        'static/meet-our-editorial-board',
        'static/privacy',
        'static/cookie-policy',
        'static/subscribe',
        'static/successful-register',
        'static/terms-and-conditions',
        'static/thank-you',
        'static/editorial-calendar',
        'static/events',
        'magazines/index',
        'magazines/show',
        'usa/show',
    ];

    /**
     * @param View $view
     */
    public function compose(View $view)
    {
        $view->with('layout', new LayoutBuilder($view->action ?? $view->getName()));
    }
}
