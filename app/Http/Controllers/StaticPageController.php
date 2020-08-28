<?php

namespace App\Http\Controllers;

class StaticPageController extends Controller
{
    public function subscribe()
    {
        return view('static.subscribe');
    }

    public function meetOurEditorialBoard()
    {
        return view('static.meet-our-editorial-board');
    }

    public function advertising()
    {
        return view('static.advertising');
    }

    public function termsAndConditions()
    {
        return view('static.terms-and-conditions');
    }

    public function privacy()
    {
        return view('static.privacy');
    }

    public function cookiePolicy()
    {
        return view('static.cookie-policy');
    }

    public function contact()
    {
        return view('static.contact');
    }

    public function thankYou()
    {
        return view('static.thank-you');
    }

    public function editorialCalendar()
    {
        return view('static.editorial-calendar');
    }

    public function successfulRegister()
    {
        return view('static.successful-register');
    }

    public function events()
    {
        return view('static.events');
    }
}
