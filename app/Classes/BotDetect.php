<?php

namespace App\Classes;

use Illuminate\Http\Request;
use Cache;

/**
 * Description of BotDetect
 *
 * @author mark
 */
class BotDetect
{
    protected $ip = false;
    protected $request;
    protected $hostname;
    protected $ip_back;
    protected $validdomains = [
        'google.com' => true,
        'googlebot.com' => true,
        'moore-wilson.net' => true

    ];
    protected $valid;
    /**
     * see https://support.google.com/webmasters/answer/1061943 for where this list comes from
     * @var array
     */
    protected $useragents = [
        /*'APIs-Google (+https://developers.google.com/webmasters/APIs-Google.html)',
        'Mediapartners-Google',
        'Mozilla/5.0 (Linux; Android 5.0; SM-G920A) AppleWebKit (KHTML, like Gecko) Chrome Mobile Safari (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)',
        'AdsBot-Google (+http://www.google.com/adsbot.html)',
        'Googlebot-Image/1.0',
        'Googlebot-News',
        'Googlebot-Video/1.0',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Safari/537.36',
        'Googlebot/2.1 (+http://www.google.com/bot.html)',
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'compatible; Mediapartners-Google/2.1; +http://www.google.com/bot.html',
        'AdsBot-Google-Mobile-Apps',*/
        'APIs-Google (+https://developers.google.com/webmasters/APIs-Google.html)',
        'Mediapartners-Google',
        'compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html',
        'AdsBot-Google (+http://www.google.com/adsbot.html)',
        'Googlebot-Image/1.0',
        'Googlebot-News',
        'Googlebot-Video/1.0',
        'compatible; Googlebot/2.1; +http://www.google.com/bot.html',
        'Googlebot/2.1 (+http://www.google.com/bot.html)',
        'compatible; Mediapartners-Google/2.1; +http://www.google.com/bot.html',
        'AdsBot-Google-Mobile-Apps',
    ];
    public function __construct(Request $request)
    {

        $this->request = $request;
    }
    /**
     * see https://support.google.com/webmasters/answer/80553
     * @return boolean
     */
    protected function _validate()
    {
        if ($this->validateUserAgent()) {
            $this->ip = $this->request->ip();
            $this->hostname = Cache::remember('botdetect_ip_' . $this->ip, 3, function () {
                return gethostbyaddr($this->ip);
            });
            $this->ip_back = Cache::remember('botdetect_hostname_' . $this->hostname, 3, function () {
                return gethostbyname($this->hostname);
            });
        }
        if ($this->ip != $this->ip_back) {
            return false;
        }
        $bits = explode('.', $this->hostname);

        if (count($bits) >= 2) {
            return isset($this->validdomains[$bits[count($bits) - 2] . '.' . $bits[count($bits) - 1]]);
        } else {
            return false;
        }
    }
    /**
     *
     * @return boolean
     */
    public function validate()
    {
        if ($this->valid === null) {
            function_exists('start_measure') ? start_measure('_validate googlebot') : null;
            $this->valid = $this->_validate();
            function_exists('stop_measure') ? stop_measure('_validate googlebot') : null;
        }
        return $this->valid;
    }
    /**
     *
     * @return boolean
     */
    protected function validateUserAgent()
    {
        $agent = $this->request->userAgent();

        foreach ($this->useragents as $agentitem) {
            if (strpos($agent, $agentitem) !== false) {
                $this->bot = $agentitem;
                return true;
            }
        }
        return false;
    }
}
