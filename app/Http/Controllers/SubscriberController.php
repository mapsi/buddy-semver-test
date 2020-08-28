<?php

namespace App\Http\Controllers;

use Flash;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Alvee\WorldPay\lib\Worldpay;
use Alvee\WorldPay\lib\WorldpayException;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;
use Carbon\Carbon;
use App\Models\Brand;
use App\Mail\AdminNewSubscriptionNotifcation;
use App\Models\AuditEvent;

class SubscriberController extends Controller
{
    public function __construct(Brand $brand = null)
    {
        $this->brand = $brand;
    }
    public function getWorldpayEnv()
    {
        return config('worldpay.server');
    }

    public function getWorldpayKey($mode = 'client')
    {
        return config('worldpay.' . $this->getWorldpayEnv() . '.' . $mode);
    }

    public function totalToPence($total)
    {
        if ($total != (int) $total) {
            $total = $total * 100;
        } else {
            $total .= '00';
        }
        return (int) ($total);
    }
    public function index()
    {

        return view('subscriber.list')->with('teams', Auth()->user()->manages);
    }
    public function getChangeDetails(Team $team)
    {

        if ($team->user_id != Auth()->user()->id) {
            Flash::error('You are not the manager for this team.');

            return redirect()->route('subscriber.index');
        }
        return view('auth.myaccount.change')->with('team', $team);
    }
    public function postChangeDetails(Request $request, Team $team, \App\Models\Brand $brand)
    {

        if ($team->user_id != Auth()->user()->id) {
            Flash::error('You are not the manager for this team.');

            return redirect()->route('subscriber.index');
        }
        $fields = [
            'password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (! Hash::check($value, auth()->user()->password)) {
                        return $fail('Old password is not correct.');
                    }
                }
            ],
            'name' => 'required',

            'address1' => 'required',
            'address2' => 'required',
            'city' => 'required',
            'state' => '',
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

        ];
        $this->validate($request, $fields);
        unset($fields['password']);
        Mail::to(config('globemedia.notifiy.' . $brand->machine_name . '.changedetails'))
            ->send(new Contact($request->only(array_keys($fields)) + [
                'Team id' => $team->id,
                'Current team name' => $team->name,
                'Manager making request' => Auth()->user()->email

            ], 'details change request'));
        Flash::error('Your details change request as been sent in.');
        return response()->redirectToRoute('subscriber.index');
    }
    public function getEmailPreferences()
    {
        return view('subscriber.email-preferences')->with(['user' => Auth()->user()]);
    }
    public function postEmailPreferences(Request $request, Brand $brand)
    {
        $user = auth()->user();
        if ($request->{$brand->machine_name . '_weekly'}) {
            $user->{$brand->machine_name . '_weekly'} = 1;
        } else {
            $user->{$brand->machine_name . '_weekly'} = 0;
        }
        if ($brand->machine_name == 'wtr') {
            if ($request->{$brand->machine_name . '_daily'}) {
                $user->{$brand->machine_name . '_daily'} = 1;
            } else {
                $user->{$brand->machine_name . '_daily'} = 0;
            }
        }
        $user->save();
        return response()->redirectToRoute('subscriber.index');
    }
    public function renew(Subscription $subscription)
    {
        if ($subscription->currency == 'free') {
            Flash::error('Your subscription has issues please contact us.');
            return redirect()->route('subscriber.index');
        }
        return view('subscriber.renew')->with('clientkey', $this->getWorldpayKey())
            ->with('currency', config('currencies.list')[$subscription->currency])->with('subscription', $subscription);
    }
    public function pay(Request $request, Subscription $subscription)
    {
        if ($subscription->team->user_id != Auth()->user()->id) {
            Flash::error('You are not the manager for this team.');

            return redirect()->route('subscriber.index');
        }
        if ($request->input('update_billing')) {
            $this->validate($request, [
                'billing_address1' => 'required',
                'billing_postcode' => 'required',
                'billing_country_id' => 'required',
            ]);
        }
        //setup payment
        $token    = $request->input('token');
        $total    = $subscription->price;
        $key      = $this->getWorldpayKey('service');
        $ips = \Alvee\WorldPay\lib\Utils::getClientIp();
        $ex = explode(',', $ips);
        if (count($ex) > 1) {
            $_SERVER['HTTP_CF_CONNECTING_IP'] = trim($ex[0]);
        }
        $worldPay = new Worldpay($key);
        $worldPay->disableSSLCheck(false);
        if ($request->input('update_billing')) {
            $subscription->team->billing_address1 = $request->input('billing_address1');
            $subscription->team->billing_address2 = $request->input('billing_address2');
            $subscription->team->billing_address3 = $request->input('billing_address3');
            $subscription->team->billing_postcode = $request->input('billing_postcode');
            $subscription->team->billing_city = $request->input('billing_city');
            $subscription->team->billing_state = $request->input('billing_state');
            $subscription->team->billing_country_id = $request->input('billing_country_id');
        }
        $subscription->team->save();


        if ($subscription->team->billing_postcode) {
            $billing_address = array(
                'address1' => $subscription->team->billing_address1,
                'address2' => $subscription->team->billing_address2,
                'address3' => $subscription->team->billing_address3,
                'postalCode' => $subscription->team->billing_postcode,
                'city' => $subscription->team->billing_city,
                'state' => $subscription->team->billing_state,
                'countryCode' => $subscription->team->billing_country_id,
            );
        } else {
            $billing_address = array(
                'address1' => $subscription->team->address1,
                'address2' => $subscription->team->address2,
                'address3' => $subscription->team->address3,
                'postalCode' => $subscription->team->postcode,
                'city' => $subscription->team->city,
                'state' => $subscription->team->state,
                'countryCode' => $subscription->team->country_id,
            );
        }

        try {
            if ($this->getWorldpayEnv() == 'sandbox') {
                $name = '3D';
                //$billing_address['postalCode'] = 'JJJJ';
            } else {
                $name = Auth()->user()->name;
            }
            $order = [
                'token' => $token,
                'amount' => $this->totalToPence($total),
                'currencyCode' => $subscription->currency,
                'name' => $name,
                'billingAddress' => $billing_address,
                'orderDescription' => $subscription->subscribable->description,
                'customerOrderCode' => 's-' . $subscription->id,
                'is3DSOrder' => true
            ];
            if ($subscription->currency == 'EUR') {
                $order['siteCode'] = 'GLOBALBUSINESSPEUR';
                $order['settlementCurrency'] = 'EUR';
            } elseif ($subscription->currency == 'USD') {
                $order['siteCode'] = 'GLOBALBUSINESSPUSD';
                $order['settlementCurrency'] = 'USD';
            }
            $response = $worldPay->createOrder($order);
            //this is becuase _SESSION is not saved
            if (isset($_SESSION['worldpay_sessionid'])) {
                request()->session()->put(
                    'worldpay_sessionid',
                    $_SESSION['worldpay_sessionid']
                );
            }
            if ($response['paymentStatus'] === 'SUCCESS') {
                request()->session()->put('orderCode', $response['orderCode']);

                return $this->paymentProcess($response, $subscription, $this->brand);
            } elseif ($response['paymentStatus'] === 'PRE_AUTHORIZED' && $response['redirectURL']) {
                request()->session()->put('orderCode', $response['orderCode']);
                return view('vendor.alvee.3d')
                    ->with('redirecturl', $response['redirectURL'])
                    ->with('token', $response['oneTime3DsToken'])
                    ->with('route', route('subscriber.auth', [$subscription->id, '_token' => csrf_token()]));
            } elseif ($response['paymentStatus'] === 'FAILED') {
                logger('payment compleated with message ' . json_encode($response));
                Flash::error('Payment has failed due to ' . $response['paymentStatusReason'] . '. If this continues please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606 quoting s-' . $subscription->id . '.');
            } else {
                logger('payment compleated with message ' . json_encode($response));
                // The card has been declined
                /*throw new \Alvee\WorldPay\lib\WorldpayException(print_r($response,
                    true));*/

                Flash::error('Payment has failed due to an unknown error, reference s-' . $subscription->id . '. Please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606.');
            }
        } catch (\Alvee\WorldPay\lib\WorldpayException $e) {
            if ($e->getMessage() == 'Name must contain valid characters') {
                Flash::error('Payment has failed due to your title/forename/surname contains invalid characters.  If this continues please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606 quoting s-' . $subscription->id . '.');
            } else {
                if (app()->environment() === 'local' && config('app.debug')) {
                    debug('Error code: ' . $e->getCustomCode() . '
                      HTTP status code:' . $e->getHttpStatusCode() . '
                      Error description: ' . $e->getDescription() . '
                      Error message: ' . $e->getMessage() . '
                      Order code: s-' . $subscription->id);
                }

                logger('Error code: ' . $e->getCustomCode() . '
                      HTTP status code:' . $e->getHttpStatusCode() . '
                      Error description: ' . $e->getDescription() . '
                      Error message: ' . $e->getMessage() . '
                      Order code: s-' . $subscription->id);

                Flash::error('Your card was declined please check the details and try again.');
                Flash::error('<a href="/info/contact">Please contact the subscriptions team</a> if you continue to have problems with your payment');
            }
            // The card has been declined
        } catch (\Exception $e) {
            logger($e->getMessage() . '
                  Order code: s-' . $subscription->id);
            Flash::error('Payment has failed due to an unknown error, reference s-' . $subscription->id . '. Please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606.');
            // The card has been declined
            logger('Error message: ' . $e->getMessage());
        }

        return Response()->redirectToRoute('subscriber.renew', [$subscription->id]);
    }
    public function paymentProcess($result, Subscription $subscription, \App\Models\Brand $brand)
    {
        $subscription->payment_details = json_encode($result);
        $subscription->payment_id = request()->session()->get('orderCode');
        $subscription->payment_provider = 'worldpay';
        $subscription->active = 1;
        $subscription->save();

        try {
            $subscription->createRenewal();
            Mail::to(config('globemedia.notifiy.' . $brand->machine_name . '.new'))
                ->send(new AdminNewSubscriptionNotifcation(Auth()->user(), $subscription));
            Mail::to(Auth()->user()->email)
                ->send(new \App\Mail\SubscriptionConfirmation(Auth()->user(), $subscription, $brand, true));
        } catch (\Exception $e) {
            logger('Error message: ' . $e->getMessage());
        }
        $event = new AuditEvent();
        $event->user_id = Auth()->user()->id;
        $event->brand_id = resolve(\App\Models\Brand::class)->id;
        $event->type =   2;
        $event->data = json_encode(['form' => [], 'dates' => [
            'start' => $subscription->start,
            'expiry' => $subscription->expiry
        ]]);
        $event->save();
        Flash::success('Payment was taken successfully');
        return Response()->redirectToRoute('subscriber.index', [$subscription->id]);
    }

    public function auth(Subscription $subscription)
    {
        //check threedsecure
        if ($paymentresult = $this->threedsecure()) {
            return $this->paymentProcess($paymentresult, $subscription, $this->brand);
        } else {
            Flash::error('Payment has failed due to an unknown error, reference s-' . $subscription->id . '. Please contact ' . $this->brand->name . ' subscriptions on +44 20 7234 0606.');
            return Response()->redirectToRoute('subscriber.index', [$subscription->id]);
        }
    }
    public function threedsecure()
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
            $_SESSION['worldpay_sessionid'] = request()->session()->get('worldpay_sessionid');
            $response                       = $worldpay->authorize3DSOrder(
                request()->session()->get('orderCode'),
                $_POST['PaRes']
            );
            if (isset($response['paymentStatus']) && $response['paymentStatus'] == 'SUCCESS') {
                return $response;
            } else {
                Flash::error('There was a problem authorising 3DS order');
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
}
