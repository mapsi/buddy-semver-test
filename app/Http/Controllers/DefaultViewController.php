<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DefaultViewController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('default', ['action' => $request->route()->action['as']]);
    }
}
