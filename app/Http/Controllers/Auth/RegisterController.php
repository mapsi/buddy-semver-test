<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Models\Brand;
use Illuminate\Http\Request;
use Jrean\UserVerification\Facades\UserVerification;
use Illuminate\Auth\Events\Registered;
use Jrean\UserVerification\Traits\VerifiesUsers;
use Flash;
use App\Models\AuditEvent;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException as ValidationValidationException;
use Jrean\UserVerification\Exceptions\UserNotFoundException;
use Jrean\UserVerification\Exceptions\UserIsVerifiedException;
use Jrean\UserVerification\Exceptions\TokenMismatchException;

class RegisterController extends Controller
{
    /*
      |--------------------------------------------------------------------------
      | Register Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users as well as their
      | validation and creation. By default this controller uses a trait to
      | provide this functionality without requiring any additional code.
      |
     */
    use RegistersUsers, AuthenticatesUsers, VerifiesUsers {
        RegistersUsers::guard insteadof AuthenticatesUsers;
        RegistersUsers::redirectPath insteadof AuthenticatesUsers;
    }

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';
    protected $redirectAfterVerification = '/verification/success';
    protected $brand;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Brand $brand = null)
    {
        $this->brand = $brand;
        $this->middleware('guest', ['except' => [
            'getVerification',
            'getVerificationError',
            'success',
            'verified',
            'postResend',
            'getResend',
        ]]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'title' => 'required|string|max:255',
            'forename' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'telephone' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $var = $this->brand->machine_name . '_weekly';
        $user = User::create([
            'name' => $data['title'] . ' ' . $data['forename'] . ' ' . $data['surname'],
            'email' => $data['email'],
            'admin' => 0,
            $var => 1 //(isset($data['weekly']) ? $data['weekly'] : 0)
        ]);
        $user->brands()->attach($this->brand->id, [
            'password' => bcrypt($data['password']),
            'created_at' =>  \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        $user->manages()->create([
            'name' => $data['company'],
            'telephone' => $data['telephone'],
            'postcode' => '',
            'city' => '',
            'state' => '',
            'country_id' => '',
        ])->members()->sync([$user->id]);

        return $user;
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('auth.register-checkemail');
    }

    public function showBrandRegistrationForm(Request $request)
    {
        if (! Session()->get('checked')) {
            return redirect()->route('register');
        }
        $user = User::where('email', Session()->get('checked'))->first();

        return view('auth.register-brand')->with(compact('user'));
    }

    public function showNewRegistationForm(Request $request)
    {
        if (! Session()->get('checked')) {
            return redirect()->route('register');
        }

        return view('auth.register');
    }

    public function checkEmail(Request $request)
    {
        $this->validate($request, [
            'g-recaptcha-response' => 'required|captcha',
            'email' => 'email|required',
        ]);

        Session()->put('checked', $request->input('email'));
        $user = User::where('email', '=', $request->input('email'))->first();
        if ($user) {
            if ($user->brands()->where('id', $this->brand->id)->count() == 0) {
                return redirect()->Route('register-login')->withInput();
            } else {
                Flash::message('Thank you for your interest in ' . $this->brand->title . '. Your email address has already been registered on ' . $this->brand->name . '. Please enter your password below or <a href="' . route('password.request') . '">reset your password</a>');

                return redirect()->Route('login')->withInput();
            }
        } else {
            return redirect()->Route('register-new')->withInput();
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        if (! Session()->get('checked')) {
            return redirect()->route('register');
        }

        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        //        $this->guard()->login($user);

        UserVerification::generate($user);
        UserVerification::send($user, 'Verify your user');
        $event = new AuditEvent();
        $event->user_id = $user->id;
        $event->brand_id = resolve(\App\Models\Brand::class)->id;
        $event->type = 0;
        $event->data = json_encode(['form' => array_keys($request->all())]);
        $event->save();

        return Response()->redirectToRoute('register.success');
    }

    public function success()
    {
        return view('auth.success');
    }

    public function verified()
    {
        return view('auth.verified');
    }

    /**
     * Handle the user verification.
     *
     * @param  string $token
     * @return \Illuminate\Http\Response
     */
    public function getVerification(Request $request, $token)
    {
        if (! $this->validateRequest($request)) {
            return redirect($this->redirectIfVerificationFails());
        }

        try {
            $user = UserVerification::process($request->input('email'), $token, $this->userTable());
            $event = new AuditEvent();
            $event->user_id = $user->id;
            $event->brand_id = resolve(\App\Models\Brand::class)->id;
            $event->type = 1;
            $event->data = json_encode([]);
            $event->save();
        } catch (UserNotFoundException $e) {
            return redirect($this->redirectIfVerificationFails());
        } catch (UserIsVerifiedException $e) {
            return redirect($this->redirectIfVerified());
        } catch (TokenMismatchException $e) {
            return redirect($this->redirectIfVerificationFails());
        }

        if (config('user-verification.auto-login') === true) {
            auth()->loginUsingId($user->id);
        }

        return redirect($this->redirectAfterVerification());
    }

    public function postResend(Request $request)
    {
        $email = [];
        if (Auth()->user()) {
            $user = Auth()->user();
        } else {
            $user = User::where('email', '=', $request->email)->first();
            $email = [
                'email' => [
                    'required',
                    function ($value, $attribute, $fail) use ($user) {
                        if (! $user) {
                            return $fail('Please enter a valid user');
                        } else {
                            if ($user->verified == 1) {
                                return $fail('User is already verified');
                            }
                        }
                    },
                ],
            ];
        }
        $this->validate($request, [
                'g-recaptcha-response' => 'required|captcha',
            ] + $email);
        session()->forget('email_verification_id');

        UserVerification::generate($user);
        UserVerification::send($user, 'Verify your user');
        Flash::message('An email has been sent to your email address with an activiation code.');

        return back();
    }

    public function getResend()
    {
        $user = Auth()->guest() ? User::find(session('email_verification_id', ''))
            : Auth()->user();

        return view('auth.verify')->with('user', $user);
    }
}
