<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Subscription;
use App\Models\SubscriptionLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\UserIpRange;
use App\Models\UserLbrDetail;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ImportSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:subscribers
                            {brand : Brand code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import active subscribers for a brand from LBR Admin';

    protected $brand;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->brand = $this->argument('brand');

        if ($this->brand == 'gcr' || $this->brand == 'gir') {
            $subscribers = $this->fetchSubscribers($this->brand);
            $subscribers = json_decode($subscribers);

            if ($subscribers) {
                $this->populateSubscribers($subscribers);
            }
        }
    }

    private function fetchSubscribers(string $brand)
    {

        $client = new Client();
        $uri = 'https://api.lbresearch.com/subscribers/' . $brand;

        $response = $client->get($uri, [
            'auth' => [
                'production',
                'pRachadrajebehejeSw54ewe'
            ]
        ]);
        $contents = $response->getBody()->getContents();

        return $contents;
    }

    private function populateSubscribers($subscribers)
    {
        $brandClass = Brand::where('machine_name', $this->brand)->first();

        $index = 1;
        foreach ($subscribers as $subscriber) {
            $user = $this->createOrUpdateUser($subscriber);

            if ($user) {
                // IP ranges
                if ($subscriber->ip_ranges) {
                    $this->addRanges($subscriber->ip_ranges, $user);
                }

                // LBR Details
                $lbr_details = [
                    'lbr_user_id' => $subscriber->lbr_user_id,
                    'lbr_org_id' => $subscriber->lbr_org_id,
                    'lbr_account_id' => $subscriber->lbr_account_id,
                ];
                $this->createOrUpdateUserLbrDetails($lbr_details, $user);

                // brand user
                $user->brands()->syncWithoutDetaching([$brandClass->id]);
                $this->updateBrandCredentials($brandClass, $user, $subscriber);

                // team
                $team = $this->createOrUpdateTeamForUser($user, $subscriber);
            } else {
                $team = null;
                $this->error($subscriber->lbr_account_id . ' user not created');
            }

            if ($team) {
                $subscription = $this->createOrUpdateSubscription($brandClass, $team, $subscriber);
            } else {
                $subscription = null;
                $this->error($subscriber->lbr_account_id . ' team not created');
            }

            if ($subscription) {
                $this->info($subscriber->lbr_account_id . ' imported');
            } else {
                $this->error($subscriber->lbr_account_id . ' not associsted with team');
            }

            // $index++;
            // if ($index == 4) {
            //     dd('stop');
            // }
        }
    }

    private function createOrUpdateUser($subscriber)
    {
        $user = User::firstOrNew(['email' => $subscriber->email]);

        $user->name = html_entity_decode($subscriber->first_name) . ' ' . html_entity_decode($subscriber->last_name);
        $user->company = html_entity_decode($subscriber->firm);
        // don't override flag for existing admins
        if (! ($user->admin > 0)) {
            $user->admin  = 0; // user
        }
        $user->verified = true;
        $user->notes = strtoupper($this->brand) . ' user auto-imported from LBR Admin on ' . Carbon::now()->toDateTimeString() . '. Reference link: ' . $subscriber->admin_link;
        $user->save();

        return $user;
    }

    private function addRanges($ranges, $user)
    {
        foreach ($ranges as $range) {
            $userIpRange = new UserIpRange();
            $userIpRange->range_from = $range->start_ip;
            $userIpRange->range_to = $range->end_ip;

            $user->ipRanges()->save($userIpRange);
        }
    }

    private function updateBrandCredentials($brand, $user, $details)
    {
        $username = html_entity_decode($details->username);
        $password = 'Temporary123!';

        // check if username unique
        $useUsername = true;

        $existingUser = DB::table('brand_user')
            ->where('username', $username)
            ->where('brand_id', $brand->id)
            ->first();

        if ($existingUser) {
            if ($existingUser->user_id != $user->id) {
                $useUsername = false;
            }
        }

        $query = DB::table('brand_user')
        ->where('brand_id', $brand->id)
        ->where('user_id', $user->id);

        $query->update([
            'password' => bcrypt($password),
            'updated_at' => \Carbon\Carbon::now(),
            'created_at' => \Carbon\Carbon::now(),
        ]);

        if ($useUsername) {
            $query->update([
                'username' => $username,
            ]);
        }
    }

    private function createOrUpdateTeamForUser($user, $subscriber)
    {
        if ($subscriber->type == 'Firmwide') {
            $name = $subscriber->firm  . ' - ' . strtoupper($this->brand) . ' Team';
        } else {
            $name = $subscriber->first_name . ' ' . $subscriber->last_name  . ' - ' . strtoupper($this->brand) . ' Team';
        }

        if ($subscriber->city) {
            $city = $subscriber->city;
        } else {
            $city = 'unknown';
        }

        if ($subscriber->country) {
            $country = $subscriber->country;
        } else {
            $country = 'GB';
        }

        $team = Team::firstOrNew(['name' => html_entity_decode($name)]);
        $team->user_id = $user->id;
        $team->company = $subscriber->firm;
        $team->city = html_entity_decode($city);
        $team->country_id = $country;
        $team->notes = 'Team auto-imported from LBR Admin on ' . Carbon::now()->toDateTimeString() . '. Reference link: ' . $subscriber->admin_link;
        $team->save();

        $team->members()->syncWithoutDetaching($user->id);

        return $team;
    }

    private function createOrUpdateSubscription($brand, $team, $subscriber)
    {
        // TO BE UPDATED BEFORE EACH BRAND IMPORT
        if ($subscriber->subscription_type == 'premium') {
            $subLevelName = strtoupper($this->brand) . ' Premium';
        } elseif ($subscriber->subscription_type == 'standard') {
            $subLevelName = strtoupper($this->brand) . ' Standard';
        } else {
            $this->error('Unknown subscription type on ' . $subscriber->lbr_account_id);
        }

        $subLevel = SubscriptionLevel::where('name', $subLevelName)->firstOrFail();

        $start = Carbon::createFromFormat("F, d Y H:i:s", $subscriber->subscription_start)->toDateTimeString();
        // adding a day on request from subs team https://globelbr.atlassian.net/browse/GXR-2171
        $end = Carbon::createFromFormat("F, d Y H:i:s", $subscriber->subscription_end)->addDay()->toDateTimeString();

        $subscription = Subscription::firstOrCreate(
            [
                'team_id' => $team->id,
                'subscribable_id' => $subLevel->id,
                'subscribable_type' => get_class($subLevel),
                'active' => 1,
            ],
            [
                'payment_provider' => 'worldpay',
                'type' => 'default',
                'start' => $start,
                'expiry' => $end,
                'currency' => 'GBP',
                //'price' => 123,
            ]
        );

        $subscription->assignRole($subLevel);

        return $subscription;
    }

    protected function createOrUpdateUserLbrDetails($attributes, $user)
    {
        if ($attributes['lbr_user_id'] || $attributes['lbr_org_id'] || $attributes['lbr_account_id']) {
            $userLbrDetail = UserLbrDetail::firstOrNew(['user_id' => $user->id]);
            $userLbrDetail->lbr_user_id = $attributes['lbr_user_id'];
            $userLbrDetail->lbr_organisation_id = $attributes['lbr_org_id'];
            $userLbrDetail->lbr_account_id = $attributes['lbr_account_id'];
            $userLbrDetail->save();
        }
    }
}
