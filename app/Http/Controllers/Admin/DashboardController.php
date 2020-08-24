<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends AppBaseController
{
    public function index()
    {
        return view('admin.dashboard');
    }
}
