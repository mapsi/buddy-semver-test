<?php

namespace App\Http\Controllers;

use MadWeb\Robots\Robots;
use App\Http\Controllers\Controller;

class RobotsController extends Controller
{
    /**
     * Generate robots.txt
     */
    public function __invoke(Robots $robots)
    {
        $robots->addUserAgent('*');
        $robots->addDisallow('/brokenPages');

        if ($robots->shouldIndex()) {
            // If on the live server, serve a nice, welcoming robots.txt.
            $robots->addUserAgent('*');
            $robots->addSitemap(url('sitemap.xml'));
            $robots->addSitemap(url('newsmap.xml'));
        } else {
            $robots->addUserAgent('Twitterbot');
            $robots->addDisallow('');
            // If you're on any other server, tell everyone to go away.
            $robots->addUserAgent('*');
            $robots->addDisallow('/');
        }

        return response($robots->generate(), 200, ['Content-Type' => 'text/plain']);
    }
}
