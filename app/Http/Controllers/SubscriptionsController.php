<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Flash;
use App\Models\Brand;
use Alvee\WorldPay\lib\Worldpay;
use Alvee\WorldPay\lib\WorldpayException;
use Mail;
use App\Mail\AdminNewSubscriptionNotifcation;
use Jrean\UserVerification\Facades\UserVerification;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\AuditEvent;

class SubscriptionsController extends Controller
{
    use AuthenticatesUsers;

    protected $brand;

    public function __construct(Brand $brand = null)
    {
        $this->brand = $brand;
    }

    public function why()
    {
        if (active_host('iam') || active_host('wtr')) {
            return view('subscribe.why');
        }

        abort(404);
    }

    public function plans()
    {
        if (active_host('iam') || active_host('wtr')) {
            return view('subscribe.plans');
        }

        abort(404);
    }

    public function listSubscriptions()
    {
        return view(
            'subscribe.list',
            [
                'products' => $this->getProductQuery()->get(),
                'noIndex' => true,
            ]
        );
    }

    public function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);
        return true;
    }

    public function auth(Request $request)
    {
        if ($request->input('password')) {
            $r = $this->login($request);
            if ($r !== true) {
                return $r;
            }
            auth()->setUser($this->guard()->user());
            return redirect()->route('subscribe.register');
        }

        return view('subscribe.auth-login', []);
    }

    public function register(Request $request)
    {
        $product = $this->getProduct($this->getStoredData('product_id'));
        if (! $product) {
            Flash::error('Please pick valid a product.');

            return redirect()->route('subscribe.list');
        } else {
            $this->putStoredData(
                'product_id',
                $this->getStoredData('product_id')
            );
            $this->putStoredData(
                'currency_id',
                $this->getStoredData('currency_id')
            );
        }
        if ($request->input('email_new')) {
            $this->validate($request, [
                'g-recaptcha-response' => 'required|captcha',
                'email_new' => 'required|email'
            ]);
            $this->putStoredData(
                'email',
                $this->getStoredData('email_new')
            );
            $user = \App\Models\User::where('email', '=', $request->input('email_new'))->first();
            if ($user) {
                return redirect()->Route('subscribe.register-login')->withInput();
            }
        } else {
            if (! auth()->user() && ! $this->getStoredData('email')) {
                return view('subscribe.auth', []);
            }
        }
        //request()->session()->put('subscribe', []);
        return view(
            'subscribe.register',
            [
                'product' => $product,
                'defaults' => function ($field) {
                    if (auth()->user()) {
                        $user = auth()->user()->toArray();
                        $user['title'] = auth()->user()->title;
                        $user['forename'] = auth()->user()->forename;
                        $user['surname'] = auth()->user()->surname;
                        $team = auth()->user()->manages()->first();
                    } else {
                        $user = [];
                        $team = false;
                    }

                    if (isset($user[$field])) {
                        return $this->getStoredData($field, $user[$field]);
                    } elseif ($team) {
                        $a = $team->toArray();
                        $b = explode('.', $field);
                        if (count($b) > 1 && $b[0] == 'team') {
                            $field = $b[1];
                        }
                        if (isset($a[$field])) {
                            return $this->getStoredData('team.' . $field, $a[$field]);
                        } elseif (isset($a[$field])) {
                            return $this->getStoredData($field);
                        }
                    } else {
                        return $this->getStoredData($field);
                    }
                }
            ]
        );
    }

    public function review(Request $request)
    {
        $product = $this->getProduct($this->getStoredData('product_id'));
        if (! $product) {
            Flash::error('Please pick valid a product.');

            return redirect()->route('subscribe.list');
        }
        $coupon = false;
        if ($request->has('coupon') && $this->billingAddress('postcode')) {
            $coupon = \App\Models\Coupon::where(
                'code',
                '=',
                $request->input('coupon')
            )
                ->where(function ($query) use ($product) {
                    $query->orWhere('product_id', '=', $product->id);
                    $query->orWhereNull('product_id');
                })
                ->where(function ($query) {
                    $query->orWhereNull('expires_at');
                    $query->orWhereRaw('expires_at >= NOW()');
                })
                ->where(function ($query) {
                    //add restriction on temp used but not completed here
                    //using the cache probaly can give you this so you can count used even if they are not yet used (add from cache)
                    $query->orWhereRaw('`used` <= `limit`');
                    $query->orWhere('limit', '=', 0);
                })
                ->first();



            if ($coupon) {
                $this->putStoredData('coupon_id', $coupon->id);
            } else {
                Flash::error('Your coupon is invalid.');
            }
        } elseif ($request->has('currency_id') && $this->billingAddress('postcode')) {
            $this->putStoredData(
                'currency_id',
                $this->getStoredData('currency_id')
            );
        } elseif ($request->has('mode')) {
            if ($request->mode == 'billing') {
                $fields = [
                    'billing_address1' => 'required',
                    'billing_address2' => 'required',
                    'billing_city' => 'required',
                    'billing_state' => 'required',
                    'billing_country_id' => 'required',
                    'billing_postcode' => 'required',
                ];
                $this->validate($request, $fields);
                $this->storeData(array_keys($fields) + ['b' => 'billing_address3']);
            } elseif ($request->mode == 'address') {
                $fields = [
                    'address1' => 'required',
                    'address2' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'country_id' => 'required',
                    'postcode' => 'required',
                ];
                $this->validate($request, $fields);
                $this->storeData(array_keys($fields) + ['b' => 'billing_address3']);
            } elseif ($request->mode == 'details') {
                $fields = [
                    'company' => 'required',
                    'title' => 'required',
                    'forename' => 'required',
                    'surname' => 'required',
                    'email' => 'required',
                ];
                $this->validate($request, $fields);
                $this->storeData(array_keys($fields) + ['b' => 'billing_address3']);
            }
        } else {
            //check the register stuff
            $fields = [
                'title' => 'required',
                'forename' => 'required',
                'surname' => 'required',
                'company' => 'required',
                'company_type' => 'required',
                'job_function' => 'required',
                'job_title' => 'required',
                'address1' => 'required',
                'address2' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country_id' => 'required',
                'postcode' => 'required',
                'billing_address1' => 'required_with:billing_postcode',
                'billing_address2' => 'required_with:billing_postcode',
                'billing_city' => 'required_with:billing_postcode',
                'billing_state' => 'required_with:billing_postcode',
                'billing_country_id' => 'required_with:billing_postcode',
                'billing_postcode' => 'required_with:billing_address1',
                'telephone' => 'required',
                'vat' => 'nullable',
                'job_function_other' => 'required_if:job_function,Other'
            ];

            //its possible that we are just confirming things
            if (! auth()->user()) {
                $fields += [
                    'email' => 'required|email|unique:users',
                    'password' => 'required|confirmed',
                ];
            }

            if ($request->has('postcode') || ! $this->getStoredData('postcode')) {
                $this->validate($request, $fields);
            }

            $this->storeData(array_keys($fields) + ['a' => 'address3', 'b' => 'billing_address3']);
        }
        $vatamount = $this->getVat();
        return view('subscribe.review')
            ->with('product', $product)
            ->with(
                'price',
                $product->prices->where(
                    'currency',
                    $this->getStoredData('currency_id')
                )->first()->price
            )
            ->with('vat', $vatamount)
            ->with('total', $this->getTotal($product))
            ->with('coupon', $coupon)
            ->with('values', function ($field) {
                return $this->getStoredData($field);
            })
            ->with('billing', function ($field) {
                return $this->billingAddress($field);
            })
            ->with(
                'clientkey',
                $this->getWorldpayKey()
            )
            ->with(
                'currency',
                config('currencies.list')[$this->getStoredData('currency_id')]
            );
    }

    public function getVat()
    {
        $vat = new \App\Classes\Vat();
        static $value;
        if (! $value) {
            $value = $vat->getVat(
                $this->billingAddress('country_id'),
                $this->billingAddress('state'),
                $this->getStoredData('company'),
                $this->getStoredData('vat')
            );
        }

        return $value;
    }

    public function getTotal($product)
    {

        $total = ($product->prices->where(
            'currency',
            $this->getStoredData('currency_id')
        )->first()->price * (1 + $this->getVat()));
        if ($this->getStoredData('coupon_id')) {
            $coupon = \App\Models\Coupon::find($this->getStoredData('coupon_id'));

            $total = $total * (1 - ($coupon->percentage / 100));
        }
        return $total;
    }

    public function getWorldpayEnv()
    {
        return config('worldpay.server');
    }

    public function getWorldpayKey($mode = 'client')
    {
        return config('worldpay.' . $this->getWorldpayEnv() . '.' . $mode);
    }

    public function carddetails(Request $request)
    {
        $product = $this->getProduct($this->getStoredData('product_id'));
        if (! $product) {
            Flash::error('Please pick valid a product.');

            return redirect()->route('subscribe.list');
        }

        return view('vendor.alvee.worldpay')->with(
            'clientkey',
            $this->getWorldpayKey()
        )->with('back', route('subscribe.review'));
    }

    protected function getOrderCode($product)
    {
        if (Auth()->user()) {
            return 'x-' . Auth()->user()->id . '-' . $product->id . '-' . date('dmyhis');
        } else {
            return 'n-' . md5($this->getStoredData('email')) . '-' . $product->id . '-' . date('dmyhis');
        }
    }

    protected function totalToPence($total)
    {
        if ($total != (int) $total) {
            $total = $total * 100;
        } else {
            $total .= '00';
        }
        return (int) ($total);
    }

    public function paymentAuth(Request $request)
    {

        $product = $this->getProduct($this->getStoredData('product_id'));
        if (! $product) {
            Flash::error('Please pick valid a product.');

            return redirect()->route('subscribe.list');
        }

        //setup payment
        $token    = $request->input('token');
        $total    = $this->getTotal($product);
        $key      = $this->getWorldpayKey('service');
        $ips = \Alvee\WorldPay\lib\Utils::getClientIp();
        $ex = explode(',', $ips);
        if (count($ex) > 1) {
            $_SERVER['HTTP_CF_CONNECTING_IP'] = trim($ex[0]);
        }
        $worldPay = new Worldpay($key);
        $worldPay->disableSSLCheck(false);
        $billing_address = array(
            'address1' => $this->billingAddress('address1'),
            'address2' => $this->billingAddress('address2'),
            'address3' => $this->billingAddress('address3'),
            'postalCode' => $this->billingAddress('postcode'),
            'city' => $this->billingAddress('city'),
            'state' => $this->billingAddress('state'),
            'countryCode' => $this->billingAddress('country_id'),
        );

        try {
            if (Request()->has('debug_name') && $this->getWorldpayEnv() == 'sandbox') {
                $name = '3D';
            } else {
                $name = trim($this->getStoredData('title') . ' ' . $this->getStoredData('forename') . ' ' . $this->getStoredData('surname'));
            }
            $order = [
                'token' => $token,
                'amount' => $this->totalToPence($total),
                'currencyCode' => $this->getStoredData('currency_id'),
                'name' => $name,
                'billingAddress' => $billing_address,
                'orderDescription' => $product->description,
                'customerOrderCode' => $this->getOrderCode($product),
                'is3DSOrder' => true
            ];
            if ($this->getStoredData('currency_id') == 'EUR') {
                $order['siteCode'] = 'GLOBALBUSINESSPEUR';
                $order['settlementCurrency'] = 'EUR';
            } elseif ($this->getStoredData('currency_id') == 'USD') {
                $order['siteCode'] = 'GLOBALBUSINESSPUSD';
                $order['settlementCurrency'] = 'USD';
            }
            $response = $worldPay->createOrder($order);

            if (isset($_SESSION['worldpay_sessionid'])) {
                $this->putStoredData(
                    'worldpay_sessionid',
                    $_SESSION['worldpay_sessionid']
                );
            }
            if ($response['paymentStatus'] === 'SUCCESS') {
                $this->putStoredData('orderCode', $response['orderCode']);

                return $this->paymentProcess($response, $product, $this->brand);
            } elseif ($response['paymentStatus'] === 'PRE_AUTHORIZED' && $response['redirectURL']) {
                $this->putStoredData('orderCode', $response['orderCode']);
                return view('vendor.alvee.3d')
                    ->with('redirecturl', $response['redirectURL'])
                    ->with('token', $response['oneTime3DsToken']);
            } elseif ($response['paymentStatus'] === 'FAILED') {
                logger('payment compleated with message ' . json_encode($response));
                Flash::error('Payment has failed due to ' . $response['paymentStatusReason'] . '. If this continues please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606 quoting ' . $this->getOrderCode($product) . '.');
            } else {
                Flash::error('Your card was declined please check the details and try again.');
                Flash::error('Please <a href="/info/contact">contact the subscriptions team</a> if you continue to have problems with your payment.');
                // The card has been declined
                /*throw new \Alvee\WorldPay\lib\WorldpayException(print_r($response,
                    true));*/
            }
        } catch (\Alvee\WorldPay\lib\WorldpayException $e) {
            if ($e->getMessage() == 'Name must contain valid characters') {
                Flash::error('Payment has failed due to your title/forename/surname contains invalid characters. If this continues please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606 quoting ' . $this->getOrderCode($product) . '.');
            } else {
                logger($this->getOrderCode($product) . ' Error code: ' . $e->getCustomCode() . '
                      HTTP status code:' . $e->getHttpStatusCode() . '
                      Error description: ' . $e->getDescription() . '
                      Error message: ' . $e->getMessage());
                Flash::error('Payment has failed due to an unknown error, reference ' . $this->getOrderCode($product) . '. Please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606.');
            }
            // The card has been declined
        } catch (\Exception $e) {
            Flash::error('Payment has failed due to an unknown error, reference ' . $this->getOrderCode($product) . '. Please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606.');
            // The card has been declined
            logger('Error message: ' . $e->getMessage());
        }
        //Flash::error('A error prevented payment');
        return redirect()->route('subscribe.worldpay.carddetails');
    }

    protected function billingAddress($field)
    {
        if ($this->getStoredData('billing_postcode')) {
            return $this->getStoredData('billing_' . $field);
        } else {
            return $this->getStoredData($field);
        }
    }

    protected function threedsecure()
    {
        $key      = $this->getWorldpayKey('service');
        $ips = \Alvee\WorldPay\lib\Utils::getClientIp();
        $ex = explode(',', $ips);
        if (count($ex) > 1) {
            $_SERVER['HTTP_CF_CONNECTING_IP'] = trim($ex[0]);
        }
        $worldpay = new Worldpay($key);
        $worldpay->disableSSLCheck(false);
        try {
            $_SESSION['worldpay_sessionid'] = $this->getStoredData('worldpay_sessionid');
            if (isset($_POST['PaRes'])) {
                $response                       = $worldpay->authorize3DSOrder(
                    $this->getStoredData('orderCode'),
                    $_POST['PaRes']
                );
            } else {
                Flash::error('There was a problem authorising 3DS order.');
                return false;
            }
            if (isset($response['paymentStatus']) && $response['paymentStatus'] == 'SUCCESS') {
                return $response;
            } else {
                Flash::error('There was a problem authorising 3DS order.');

                return false;
            }
        } catch (WorldpayException $e) {
            debug('Error code: ' . $e->getCustomCode() . '

            HTTP status code:' . $e->getHttpStatusCode() . '

            Error description: ' . $e->getDescription() . '

            Error message: ' . $e->getMessage());
            $response = false;
        }

        return $response;
    }

    public function paymentProcess($result, $product, \App\Models\Brand $brand)
    {
        //create user
        $user = auth()->user();
        if (! $user) {
            $user           = new \App\Models\User();
            $user->email    = $this->getStoredData('email');
            $user->password = \bcrypt($this->getStoredData('password'));
            $user->name     = $this->getStoredData('title') . ' ' . $this->getStoredData('forename') . ' ' . $this->getStoredData('surname');
            $user->job_function = $this->getStoredData('job_function');
            $user->job_title = $this->getStoredData('job_title');
            $user->admin = 0;
            $user->company = $this->getStoredData('company');
            $user->verified = 1;
            $var1 = $this->brand->machine_name . '_weekly';
            $user->$var1 = 1;
            if ($this->brand->machine_name == 'wtr') {
                $var2 = $this->brand->machine_name . '_daily';
                $user->$var2 = 1;
            }

            $user->save();
            $user->brands()->attach($this->brand->id);
            //log the user in
            auth()->login($user);
        } else {
            $user->name = $this->getStoredData('title') . ' ' . $this->getStoredData('forename') . ' ' . $this->getStoredData('surname');
            $var1 = $this->brand->machine_name . '_weekly';
            $user->$var1 = 1;
            $user->company = $this->getStoredData('company');
            if ($this->brand->machine_name == 'wtr') {
                $var2 = $this->brand->machine_name . '_daily';
                $user->$var2 = 1;
            }
            $user->brands()->syncWithoutDetaching([$this->brand->id]);
            $user->save();
        }
        //create/update team

        $team = $user->manages()->first();
        if (! $team) {
            $team          = new \App\Models\Team();
            $team->user_id = $user->id;
        }
        foreach ($team->fillable as $field) {
            if ($field != 'user_id') {
                if ($field == 'name') {
                    $team->$field = $this->getStoredData('company');
                    $team->company = $this->getStoredData('company');
                } else {
                    $team->$field = $this->getStoredData($field);
                }
            }
        }

        $team->save();

        //as we are single add make it so the user benifits
        $team->members()->syncWithoutDetaching($user->id, false);

        //create subscription
        $subscription = $team->subscriptions()->create([
            'product_id' => $product->id,
            'price' => $this->getTotal($product),
            'currency' => $this->getStoredData('currency_id'),
            'payment_details' => json_encode($result),
            'payment_id' => $this->getStoredData('orderCode'),
            'type' => $product->type,
            'payment_provider' => 'worldpay',
            'active' => true,
            'start' => \Carbon\Carbon::now(),
            'expiry' => \Carbon\Carbon::now()->addMonths($product->duration)
        ]);
        $subscription->createRenewal();
        if ($this->getStoredData('coupon_id')) {
            $coupon       = \App\Models\Coupon::find($this->getStoredData('coupon_id'));
            $coupon->used = $coupon->used + 1;
            $coupon->save();
        }
        $store = request()->session()->get('subscribe', []);
        request()->session()->put('subscribe', []);
        try {
            Mail::to(config('globemedia.notifiy.' . $brand->machine_name . '.new'))
                ->send(new AdminNewSubscriptionNotifcation(
                    $user,
                    $subscription,
                    $coupon ?? null
                ));
            Mail::to($user->email)
                ->send(new \App\Mail\SubscriptionConfirmation($user, $subscription, $brand, false));
            $event = new AuditEvent();
            $event->user_id = $user->id;
            $event->brand_id = resolve(\App\Models\Brand::class)->id;
            $event->type =   2;
            $event->data = json_encode(['form' => array_keys($store), 'dates' => [
                'start' => $subscription->start,
                'expiry' => $subscription->expiry
            ]]);
            $event->save();
        } catch (\Exception $e) {
        }
        return response()->redirectToRoute('subscribe.success');
    }

    public function paymentTake()
    {
        $product = $this->getProduct($this->getStoredData('product_id'));
        if (! $product) {
            Flash::error('Please pick valid a product.');

            return redirect()->route('subscribe.list');
        }
        //check threedsecure
        if ($paymentresult = $this->threedsecure()) {
            return $this->paymentProcess($paymentresult, $product, resolve(\App\Models\Brand::class));
        }
        return redirect()->route('subscribe.worldpay.carddetails');
    }

    public function success()
    {
        return view('subscribe.success');
    }

    protected function getProductQuery()
    {
        return Product::where('type', '=', 'default')->whereHas(
            'brands',
            function ($query) {
                $query->where('id', '=', $this->brand->id);
            }
        );
    }

    protected function getProduct($id)
    {
        if (! $id) {
            return false;
        }
        return $this->getProductQuery()->where('id', '=', $id)->first();
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    protected function getStoredData($name, $default = null)
    {
        $store = request()->session()->get('subscribe', []);
        if ($name == 'team.name') {
            $name = 'company';
        }
        if (request()->get($name, false)) {
            return request()->get($name, false);
        } elseif (isset($store[$name])) {
            return $store[$name];
        }

        $b = explode('.', $name);
        if ($b[0] == 'team' && isset($store[$b[1]])) {
            return $store[$b[1]];
        }
        return $default;
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    protected function putStoredData($name, $value)
    {
        $store = request()->session()->get('subscribe', []);

        $store[$name] = $value;
        request()->session()->put('subscribe', $store);
    }

    protected function storeData(array $names)
    {
        foreach ($names as $name) {
            if (request()->has($name)) {
                $this->putStoredData($name, request()->input($name));
            }
        }
    }
}
