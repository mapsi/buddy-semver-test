<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\IpCheck;
use App\Models\UserIpRange;
use App\Models\Brand;
use App\Models\User;

class HelperController extends Controller
{
    function ipCheck(Request $request)
    {
        $userIps = collect();

        $brands = Brand::all();

        $this->validate($request, [
            'ip' => 'ip',
            'brand' => 'integer|in:0,' . $brands->pluck('id')->implode(',')
        ]);

        $userIps = collect();
        if ($request->has('ip')) {
            $userIps = UserIpRange::validIp($request->get('ip'));
        }

        $brand = $request->get('brand');

        if ($brand != 0 && $request->has('ip') && $userIps->isNotEmpty()) {
            $users = User::whereIn('id', $userIps->pluck('user_id')->toArray())->get();

            $brand = $brands->find($brand);

            $users = $users->filter(function ($user) use ($brand) {
                return $user->isSubscriberOfBrand($brand);
            });

            if ($users->isEmpty()) {
                $userIps = collect();
            } else {
                $userIps = $userIps->filter->hasUserIds($users->pluck('id')->toArray());
            }
        }

        $brands = $brands->pluck('machine_name', 'id')->toArray();

        return view('helper/ip-check', compact('userIps', 'brands'));
    }
}
