<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class DFPComposer
{

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

        $varables = $view->getData();

        $failsafe = false;
        if (isset($varables['active_article'])) {
            $failsafe = $varables['active_article'];
        } elseif (isset($varables['active_page'])) {
            $failsafe = $varables['active_page'];
        }

        $this->withVariable($view, 'topic', $failsafe, $varables);
        $this->withVariable($view, 'sector', $failsafe, $varables);
        $this->withVariable($view, 'region', $failsafe, $varables);

        //$pageid =  request()->getRequestUri();
        $pageid = false;
        if ($failsafe) {
            // we're not goint to be using the node id anymore ...
        } elseif (request('firm') && is_object(request('firm'))) {
            $pageid = request('firm')->uuid;
        } elseif (request('individual') && is_object(request('individual'))) {
            $pageid = request('individual')->uuid;
        } elseif (request('jurisdiction') && is_object(request('jurisdiction'))) {
            $pageid = request('jurisdiction')->name;
        }

        if ($pageid) {
            $view->with('dfp_page_id', $pageid);
        }
    }
    public function withVariable($view, $variable, $failsafe, $varables)
    {
        $plural = \Illuminate\Support\Pluralizer::plural($variable);

        $method = 'get' . ucfirst($plural);

        if (isset($varables['active_' . $variable]) && is_object($varables['active_' . $variable])) {
            $view->with('dfp_' . $variable, $varables['active_' . $variable]->name);
        } elseif ($failsafe && $res = $failsafe->$method()->first()) {
            $view->with('dfp_' . $variable, $res->getName());
        }
    }
}
