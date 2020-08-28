<?php

namespace App\Http\Controllers;

use App\Models\InternationalDirectoryJurisdiction;
use Illuminate\Http\Request;

class InternationalDirectoryJurisdictionController extends Controller
{
    public function index()
    {
        $jurisdictions = InternationalDirectoryJurisdiction::with(['entries.media'])->get();

        return view('international-directory-jurisdictions.index', compact('jurisdictions'));
    }
}
