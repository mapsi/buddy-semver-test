<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Brand;

class GatingController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        $brand = Resolve(Brand::class);

        if (! $user) {
            $view = "login";
        } else {
            if ($user->isSubscriber()) {
                $view = "upgrade";
            } else {
                $view = "subscribe";
            }
        }

        if ($brand->machine_name == 'wtr' || $brand->machine_name == 'iam') {
            return view("ajax.gating.wtr_iam.$view");
        } else {
            return view("ajax.gating.$view");
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function test()
    {
        return view('ajax.gating.test');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function preview(string $gate)
    {
        $brand = Resolve(Brand::class);

        switch ($gate) {
            case "3D127E45-210F-4B7F-9B93-1623580DAEF3":
                $view = "login";
                break;
            case "CA9DDDCA-6DF2-4E82-85E9-293FA9BBD3F3":
                $view = "subscribe";
                break;
            case "B9D6AD22-7B17-471F-91AA-1DB2E32800E4":
                $view = "upgrade";
                break;
        }

        if ($brand->machine_name == 'wtr' || $brand->machine_name == 'iam') {
            return view("ajax.gating.wtr_iam.$view");
        } else {
            return view("ajax.gating.$view");
        }
    }
}
