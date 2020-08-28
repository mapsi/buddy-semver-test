<?php

namespace App\Http\ViewComposers;

use Share;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ShareThisComposer
{
    /**
     * The user repository implementation.
     *
     * @var UserRepository
     */

    /**
     * Create a new profile composer.
     *
     * @param UserRepository $users
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        if ($this->isPreviewPage()) {
            $view->with('links', []);
            return;
        }

        $networks = [
            'linkedin' => [
                'class' => 'fab fa-linkedin-in',
                'title' => 'LinkedIn',
                'color' => '#0077b5',
            ],
            'twitter' => [
                'class' => 'fab fa-twitter',
                'title' => 'Twitter',
                'color' => '#1da1f2',
            ],
            'facebook' => [
                'class' => 'fab fa-facebook-f',
                'title' => 'Facebook',
                'color' => '#3b5998',
            ],
        ];

        $links = [];
        $services = Share::load(url()->current(), app(\App\Models\Brand::class)->title)->services();

        foreach ($services as $network => $link) {
            if (isset($networks[$network])) {
                $links[$network] = [
                    'link' => $link,
                    'class' => $networks[$network]['class'],
                    'title' => $networks[$network]['title'],
                    'color' => $networks[$network]['color'],
                ];
            } else {
                $links[$network] = [
                    'link' => $link,
                    'class' => 'fas fa-times-circle',
                    'title' => $network,
                    'color' => '#657786',
                ];
            }
        }

        $view->with('links', $links);
    }

    /**
     * @return bool
     */
    private function isPreviewPage()
    {
        return Str::contains(url()->current(), 'preview');
    }
}
