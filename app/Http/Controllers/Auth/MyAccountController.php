<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class MyAccountController extends Controller
{
    function getEdit()
    {
        return view('auth.myaccount.changepassword');
    }
    function postEdit(Request $request)
    {
        $this->validate($request, [
            'new_password' => 'required|confirmed|min:6',
            'password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (! Hash::check($value, auth()->user()->password)) {
                        return $fail('Old password is not correct.');
                    }
                }
            ]
        ]);

        $user = auth()->user();
        $user->password = bcrypt($request->new_password);
        $user->save();
        \Flash::message('Password changed successfully');
        return Response()->redirectToRoute('profile.edit');
    }
}
