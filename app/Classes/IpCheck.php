<?php

namespace App\Classes;

use App\Models\IpRange;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionLevel;
use App\Models\User;

/**
 * This class is a collection of functions for working with Ips and CDIRs
 *
 * @author mark
 */
class IpCheck
{

    public function checkIps($ip, $range = false, $withsubscription = true, $brand = false)
    {
        if ($range && $this->ipCidrCheck($ip, $range->range)) {
            return collect([$range]);
        }
        $query = IpRange::select('ip_ranges.*');
        if (! $brand) {
            $brand = resolve(\App\Models\Brand::class);
            if ($brand) {
                if (SubscriptionLevel::usingThis($brand->machine_name)) {
                    $subscriptions = Subscription::with('team.members.ips')
                        ->where('subscribable_type', SubscriptionLevel::class)
                        ->current()
                        ->has('team.members.ips')
                        ->get();

                    return $subscriptions->map(function (Subscription $subscription) use ($ip) {
                        return $subscription->team->members->transform(function (User $member) use ($ip) {
                            return $member->ips->filter(function (IpRange $item) use ($ip) {
                                return $this->ipCidrCheck($ip, $item->range);
                            });
                        });
                    })->flatten();
                }

                $brand = $brand->id;
            }
        }
        if ($brand || $withsubscription) {
            $query->leftJoin('users', 'users.id', '=', 'ip_ranges.user_id')
                ->leftJoin('team_user', 'users.id', '=', 'team_user.user_id')
                ->leftJoin('teams', 'teams.id', '=', 'team_user.team_id')
                ->leftJoin('subscriptions', 'teams.id', '=', 'subscriptions.team_id')
                ->groupBy(['ip_ranges.id', 'ip_ranges.range', 'ip_ranges.name', 'ip_ranges.created_at', 'ip_ranges.updated_at', 'ip_ranges.user_id']);
        }

        if ($withsubscription) {
            $query
                //only if you have a active subscription
                ->where('subscriptions.active', 1)
                ->where('subscriptions.start', '<=', now())
                ->where(
                    'subscriptions.expiry',
                    '>=',
                    \DB::raw('DATE_SUB(now(),INTERVAL 1 MONTH)')
                );
        }

        if ($brand) {
            $query->leftJoin('products', 'products.id', '=', 'subscriptions.subscribable_id');
            $query->leftJoin('product_brands', 'products.id', '=', 'product_brands.product_id');
            $query->where('subscriptions.subscribable_type', Product::class);
            $query->where('product_brands.brand_id', $brand);
        }
        return $query->get()->filter(function ($item) use ($ip) {
            return $this->ipCidrCheck($ip, $item->range);
        });
    }

    /**
     *  from http://php.net/manual/en/ref.network.php
     * @param type $IP
     * @param type $CIDR
     * @return type
     */
    public function ipCidrCheck($IP, $CIDR)
    {
        @list($net, $mask) = preg_split("/\//", $CIDR);
        if (! $mask) {
            $a = ip2long($IP);
            $b = ip2long($net);
            if (! $a || ! $b) {
                return false;
            }
            return ip2long($IP) == ip2long($net);
        }
        $ip_net  = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);

        $ip_ip = ip2long($IP);

        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    public function globeIp2Ip($ip)
    {
        return collect(explode('.', $ip))
            ->map(function ($value) {
                //clean the value (it should be a int not a 0 leading)
                return (int) $value;
            })
            ->implode('.');
    }

    /**
     * Tweeked version of https://www.ip2location.com/tutorials/how-to-convert-ip-address-range-into-cidr
     * @param type $ipStart
     * @param type $ipEnd
     * @return array
     */
    public function ip2cidr($ipStart, $ipEnd)
    {
        if (is_string($ipStart) || is_string($ipEnd)) {
            $ipEnd   = $this->globeIp2Ip($ipEnd);
            $ipStart = $this->globeIp2Ip($ipStart);
            $start   = ip2long($ipStart);
            $end     = ip2long($ipEnd);
        } else {
            $start = $ipStart;
            $end   = $ipEnd;
        }
        //dont bother if the start and end are the same just return one value
        if ($start == $end) {
            return [$ipStart];
        }
        $result = [];

        while ($end >= $start) {
            $maxSize = 32;
            while ($maxSize > 0) {
                $mask     = hexdec($this->iMask($maxSize - 1));
                $maskBase = $start & $mask;
                if ($maskBase != $start) {
                    break;
                }
                $maxSize--;
            }
            $x       = log($end - $start + 1) / log(2);
            $maxDiff = floor(32 - floor($x));

            if ($maxSize < $maxDiff) {
                $maxSize = $maxDiff;
            }

            $ip    = long2ip($start);
            array_push($result, "$ip/$maxSize");
            $start += pow(2, (32 - $maxSize));
        }
        return $result;
    }

    public function iMask($s)
    {
        return base_convert((pow(2, 32) - pow(2, (32 - $s))), 10, 16);
    }

    public function binPad($num)
    {
        return str_pad(decbin($num), 8, '0', STR_PAD_LEFT);
    }

    /**
     * https://stackoverflow.com/a/13609706 is the basis of this function
     * @param String $a a CIDR or IP
     * @param String $b a CIDR or IP
     * @return type
     */
    public function cidrIntersect($a, $b)
    {

        $ip1   = $a;
        $ip2   = $b;
        $regex = '~(\d+)\.(\d+)\.(\d+)\.(\d+)/(\d+)~';

        preg_match($regex, $ip1, $ip1);
        preg_match($regex, $ip2, $ip2);
        if (! isset($ip1[5]) && ! isset($ip2[5])) {
            return $a === $b;
        } elseif (! isset($ip1[5])) {
            return $this->ipCidrCheck($a, $b);
        } elseif (! isset($ip2[5])) {
            return $this->ipCidrCheck($b, $a);
        }
        $mask = min($ip1[5], $ip2[5]);

        $ip1 = substr(
            $this->binPad($ip1[1]) . $this->binPad($ip1[2]) .
                $this->binPad($ip1[3]) . $this->binPad($ip1[4]),
            0,
            $mask
        );

        $ip2 = substr(
            $this->binPad($ip2[1]) . $this->binPad($ip2[2]) .
                $this->binPad($ip2[3]) . $this->binPad($ip2[4]),
            0,
            $mask
        );
        return $ip1 === $ip2;
    }
}
