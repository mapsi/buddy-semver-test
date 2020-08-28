<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IpAuthenticate;
use Illuminate\Http\Request;

class IpController extends Controller
{
    public function authenticate(Request $request)
    {
        $ipId = IpAuthenticate::checkIpRangeOnDatabaseFromRequestAndLogViewOnSuccess($request);

        if ($ipId) {
            return response()->json([
                'state' => 'authorised',
            ])->cookie('ip_authentication', $ipId);
        } else {
            return response()->json([
                'state' => 'unauthorised',
            ]);
        }
    }
}
