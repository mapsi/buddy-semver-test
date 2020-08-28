<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorldpayController extends Controller
{
    function worldpay()
    {
        return view('vendor/alvee/worldpay', [
            'clientkey' => config('worldpay.sandbox.client')
        ]);
    }

    function charge(\Illuminate\Http\Request $request)
    {
    }
}
