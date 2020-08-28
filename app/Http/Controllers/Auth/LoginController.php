<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\Brand;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Jrean\UserVerification\Exceptions\UserNotVerifiedException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var Brand
     */
    protected $brand;

    protected $subscribedError = false;

    /**
     * LoginController constructor.
     *
     * @param Brand|null $brand
     */
    public function __construct(Brand $brand = null)
    {
        $this->brand = $brand;

        $this->middleware('guest')->except(['logout', 'getRegister', 'postRegister']);
    }

    public function showLoginForm(Request $request)
    {
        if ($request->query('ReturnUrl')) {
            $request->session()->put('url.intended', RedirectIfAuthenticated::returnedUrl($request));
        }

        return view('auth.login');
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        if ($this->guard()->attempt($this->credentials($request), $request->filled('remember'))) {
            return $this->validSubscriptionOfUserOnBrand($this->guard()->user());
        }

        return false;
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [
                $this->subscribedError ? trans('auth.subscription') : trans('auth.failed'),
            ],
        ]);
    }

    /**
     * @return string
     */
    protected function redirectTo()
    {
        $user = $this->guard()->user();
        if ($user->verified == 0) {
            auth()->logout();
            session()->put('email_verification_id', $user->id);

            return route('email-verification.resend');
        }

        if ($this->brand && $user->brands()->where('id', $this->brand->id)->count() == 0) {
            return get_host_config('multisite_registration', '/login/' . $this->brand->machine_name . '/register');
        }

        $this->validateAdminLogin($user);

        return url()->previous();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getRegister()
    {
        return view('auth.cross-register');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRegister()
    {
        $var = $this->brand->machine_name . '_weekly';
        $user = Auth()->user();
        $user->$var = 1;
        $user->brands()->syncWithoutDetaching([$this->brand->id]);
        $user->save();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * @return void
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user->verified == 0) {
            return;
        }

        $this->informAnalytics();

        \Cookie::forget('ip_authentication');

        $intendedUrl = RedirectIfAuthenticated::returnedUrl($request);
        if (RouteServiceProvider::HOME !== $intendedUrl) {
            request()->session()->put('url.intended', $intendedUrl);
        }
    }

    private function informAnalytics()
    {
        session()->flash('analytics_userid', $this->guard()->user()->id);
        session()->flash('analytics_email', $this->guard()->user()->email);
        session()->flash('analytics_action', 'login');
    }

    /**
     * @param Authenticatable $user
     * @return string
     */
    private function validateAdminLogin(Authenticatable $user)
    {
        // @TODO(J): Str::contains MUST be replaced with active_host helper and move the fixed text to translation file
        if (! Str::contains(request()->getHost(), 'admin') || $user->isAdmin()) {
            return true;
        }

        auth()->logout();
        session()->put('flash_notification', [
            [
                'message' => 'You don\'t have enough permissions. Please contact an administrator',
                'level' => 'warning'
            ],
        ]);

        return route('login');
    }

    /**
     * @return bool
     */
    protected function validSubscriptionOfUserOnBrand(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! get_host_config('subscribed_to_login', false)) {
            if (! $user->isVerified()) {
                $this->guard()->logout();
                throw new UserNotVerifiedException();
            }
            return true;
        }


        if ($user->isEditor()) {
            $editorOfBrand = $user->brands->first(function (Brand $userBrand) {
                return $userBrand->id == $this->brand->id;
            });

            if ($editorOfBrand) {
                return true;
            }
        }

        if ($user->isSubscriberOfBrand($this->brand)) {
            return true;
        }

        $this->subscribedError = true;
        $this->guard()->logout();

        return false;
    }

    protected function loggedOut(Request $request)
    {
        Cookie::queue(Cookie::forget('gxrauth-jwt'));
    }
}
