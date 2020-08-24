<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class EventsMenuComposer
{
    /**
     * The user repository implementation.
     *
     * @var UserRepository
     */

    /**
     * Create a new profile composer.
     *
     * @param  UserRepository  $users
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {

        $menu = [];
        if ($view->event) {
            $map             = [
                'home' => [],
                'about' => [
                    'children' => [
                        'about_why-attend',
                        'about_previous-years',
                        'about_pricing',
                        'about_other-events',
                        'about_testimonials',
                        'about_galleries',
                    ]
                ],
                'about_why-attend' => [],
                'about_previous-years' => [],
                'about_pricing' => [],
                'about_other-events' => [],
                'about_testimonials' => [],
                'about_galleries' => [],
                'programme' => [],
                'speakers' => [],
                'sponsors' => [],
                'resources' => [
                    'children' => [
                        'resources_news',
                        'resources_analyst-commentary',
                        'resources_downloadable-content',
                    ]
                ],
                'resources_news' => [],
                'resources_analyst-commentary' => [],
                'resources_downloadable-content' => [],
                'information-centre' => [
                    'children' => [
                        'information-centre_venue',
                        'information-centre_accommodation',
                        'information-centre_travel-information',
                        'information-centre_accreditation',
                        'information-centre_faq',
                        'information-centre_contact'
                    ]
                ],
                'information-centre_venue' => [],
                'information-centre_accommodation' => [],
                'information-centre_travel-information' => [],
                'information-centre_accreditation' => [],
                'information-centre_faq' => [],
                'information-centre_contact' => [],
            ];

            $menu_options    = $view->event->MenuOptions->where('enabled', true);

            foreach ($map as $name => $item) {
                if (
                    sizeof(explode("_", $name)) == 1 && //if first level
                    count($menu_options->where('itemarea', $name)) && //if there is a menuOption instance with this itemarea
                    $menu_options->where('itemarea', $name)->first()->enabled
                ) { //if the menuOption instance is enabled
                    $path = $name != 'home' ? $name : '';

                    $menu[$name] = [
                        'path' => $view->event->getBasePath() . '/' . $path,
                        'name' => $menu_options->where('itemarea', $name)->first()->label,
                    ];

                    if (isset($item['children'])) {
                        foreach ($item['children'] as $child_name) {
                            if (
                                count($menu_options->where(
                                    'itemarea',
                                    $child_name
                                )) && $menu_options->where(
                                    'itemarea',
                                    $child_name
                                )->first()->enabled
                            ) {
                                $menu[$name]['children'][$child_name] = [
                                    'path' => $view->event->getBasePath() . '/' . str_replace(
                                        '_',
                                        '/',
                                        $child_name
                                    ),
                                    'name' =>  $menu_options->where('itemarea', $child_name)->first()->label,
                                ];
                            }
                        }
                    }
                }
            }
        }
        $view->with('menu', $menu);
    }
}
