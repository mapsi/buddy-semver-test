<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Send a welcome email (password reset link) to existing user registering on new brand.
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendWelcomeEmail(Request $request)
    {
        $this->validateEmail($request);

        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            $request->session()->flash('message', 'User with ' . $request->email . ' email not found');
            $request->session()->flash('alert-class', 'alert-danger');
            return redirect()->back();
        }

        //Create Password Reset Token
        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => bcrypt($token),
            'created_at' => Carbon::now(),
            'brand_id' => resolve(Brand::class)->id,
        ]);

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to set their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $user->sendWelcomeNotification($token);

        $request->session()->flash('message', 'Email sent to ' . $request->email);
        $request->session()->flash('alert-class', 'alert-success');
        return redirect()->back();
    }
}
