<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class SideMenuComposer
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
        $view->with('directory', $view->getData()['directory']);
    }
}
