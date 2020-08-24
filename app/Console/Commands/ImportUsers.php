<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Team;
use Carbon\Carbon;
use App\Models\Product;

class ImportUsers extends Command
{
    /* on my local stack these are the times for how long it took for a full import
     *
     * Starting World trade mark review subscribers
      1183/1183 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%  1 min/1 min  30.0 MiB
      Starting IAM media subscribers
      1852/1852 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%  1 min/1 min  40.0 MiB
      Starting World trade mark review registered
      54182/54182 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% 50 mins/50 mins 360.5 MiB
      Starting IAM media registered
      61698/61698 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% 54 mins/54 mins 519.0 MiB

     */
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:import-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description    = 'Import users from Global media';
    protected $sites          = [
        'World trade mark review subscribers' => [
            'brand' => 'wtr',
            'url' => 'http://www.worldtrademarkreview.com/api2/users/subscribers',
        ],
        'IAM media subscribers' => [
            'brand' => 'iam',
            'url' => 'http://www.iam-media.com/api2/users/subscribers'
        ],
        'World trade mark review registered' => [
            'brand' => 'wtr',
            'url' => 'http://www.worldtrademarkreview.com/api2/users/registered',
        ],
        'IAM media registered' => [
            'brand' => 'iam',
            'url' => 'http://www.iam-media.com/api2/users/registered',
        ],
    ];
    protected $users          = [];
    protected $emails         = [];
    protected $oneemailtwoids = [];
    protected $noemail        = [];
    protected $grouped        = [];
    protected $list           = [];
    protected $country_corrections = [
        'USA' => 'United States',
        'Russia' => 'Russian Federation',
        'RUSSIA' => 'Russian Federation',
        'CZECH REPUBLIC' => 'Czech Republic',
        'GERMANY' => 'Germany',
        'NORWAY' => 'Norway',
        'South Korea' => 'Republic of Korea',
        'SWITZERLAND' => 'Switzerland',
        'JAPAN' => 'Japan',
        'Serbia' => 'Republic of Serbia',
        'CHINA' => 'China',
        'Curaçao' => 'Curacao',
        'Turks & Caicos Islands' => 'Turks and Caicos Islands',
        'Brunei' => 'Brunei Darussalam',
        'ITALY' => 'Italy',
        'FRANCE' => 'France',
        'MALAYSIA' => 'Malaysia',
        'usa' => 'United States',
        'NETHERLANDS' => 'Netherlands',
        'SWEDEN' => 'Sweden',
        'Syria' => 'Syrian Arab Republic',
        'Moldova' => 'Moldova, Republic of',
        'Laos' => 'Lao People\'s Rep.',
        'British Virgin Islands' => 'Virgin Islands (British)',
        'ISRAEL' => 'Israel',
        'Congo-Brazzaville' => 'Congo',
        'St Kitts & Nevis' => 'Saint Kitts and Nevis',
        'Palestine' => 'Palestinian Territory, Occupied',
        'Democratic Republic of Congo' => 'Congo',
        'North Korea' => 'People\'s Rep. Korea',
        'Surinam' => 'Suriname',
        'AUSTRIA' => 'Austria',
        'SPAIN' => 'Spain',
        'FINLAND' => 'Finland',
        'BRAZIL' => 'Brazil',
        'BELGIUM' => 'Belgium',
        'AUSTRALIA' => 'Australia',
        'CANADA' => 'Canada',
        'INDIA' => 'India',
        'DENMARK' => 'Denmark',
        'HUNGARY' => 'Hungary',
        'US Virgin Islands' => 'Virgin Islands (U.S.)',
        'SINGAPORE' => 'Singapore',
        'POLAND' => 'Poland',
        'MEXICO' => 'Mexico',
        'Montenegro' => 'Republic of Montenegro',
        'Saint Vincent and the Grenadines' => 'St. Vincent & Grenadines',
        'South Sudan' => 'Sudan',
        'PORTUGAL' => 'Portugal',
        'TURKEY' => 'Turkey',
        'THAILAND' => 'Thailand'
    ];
    protected $providers           = [
        'Paid subscriber' => 'worldpay',
        'Paid Subscriber' => 'worldpay',
    ];
    protected $products            = [
    ];
    protected $days                = [];
    protected $brands = [];
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
        ini_set('memory_limit', '700M');
        \DB::transaction(function () {
            $users = User::get();
            $users->each(function ($it) {
                $this->users['new-' . $it->id]          = [
                    'email' => $it->email,
                    'user' => $it,
                    'count' => 1
                ];
                $this->emails[strtolower($it->email)] = $it;
            });
            $this->brands = [
              'wtr' => \App\Models\Brand::findByMachineName('wtr')  ,
              'iam' => \App\Models\Brand::findByMachineName('iam')
            ];
            $wtr = new Product([
                'price' => 0,
                'users' => 0,
                'name' => 'WTR Legacy Import',
                'description' => 'Imported WTR users subscriptions will be attached to this',
                'duration' => 0,
                'type' => 'legacy'
            ]);
            $wtr->save();
            $wtr->brands()->sync([$this->brands['wtr']->id]);

            $iam = new Product([
                'price' => 0,
                'users' => 0,
                'name' => 'IAM Legacy Import',
                'description' => 'Imported IAM users subscriptions will be attached to this',
                'duration' => 0,
                'type' => 'legacy'
            ]);
            $iam->save();
            $iam->brands()->sync([$this->brands['iam']->id]);

            $this->products = [
                'wtr' => $wtr,
                'iam' => $iam,
            ];
            foreach ($this->sites as $site => $settings) {
                $this->import($site, $settings);
            }

            $flatten = [];

            @unlink('oneemailmultipleids.csv');
            $fp = fopen('oneemailmultipleids.csv', 'w+');
            foreach ($this->oneemailtwoids as $email => $ids) {
                fputcsv($fp, ['email' => $email] + $ids);
            }
            fclose($fp);

            //throw new \Exception('no');
        });
    }

    protected function import($site, $settings)
    {

        $products = \App\Models\Product::get();
        $this->info('Starting ' . $site);
        $client   = new \GuzzleHttp\Client([
            'base_uri' => $settings['url'],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        ;
        $users    = json_decode($client->get('')->getBody());

        if (! $users) {
            $this->error('Unable to import ' . $site . ' no results');
            return;
        }
        $progress_bar = $this->output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');

        $progress_bar->start(count($users));

        $data = [];
        foreach ($users as $user) {
            $this->user($user, $settings);
            foreach ($user->Subscriptions as $sub) {
                $date = Carbon::parse($sub->startDate)->diffInYears(Carbon::parse($sub->endDate));
                if (! $sub->endDate || ! $sub->startDate) {
                    dd($$user);
                }
                if (! isset($this->days[$settings['brand']])) {
                    $this->days[$settings['brand']] = [];
                }
                $this->days[$settings['brand']][$date] = isset($this->days[$settings['brand']][$date])
                        ? $this->days[$settings['brand']][$date] + 1 : 1;
                if (isset($this->list[$sub->sourceReason])) {
                    $this->list[$sub->sourceReason] ++;
                } else {
                    $this->list[$sub->sourceReason] = 1;
                }
            }

            $progress_bar->advance();
        }


        $progress_bar->finish();

        $this->output->newLine();
    }

    protected function user($record, $settings)
    {
        $record->emailAddress = strtolower($record->emailAddress); //more duplicates than thought
        if (is_null($record->emailAddress)) {
            //$this->error($record->userId .' has no email');
            $this->noemail[] = $record->friendlyId;
            return;
        }
        if (isset($this->users['mw-' . ($record->friendlyId ?? $record->userId)])) {
            $this->users['mw-' . ($record->friendlyId ?? $record->userId)]['count'] ++;
        } else {
            $this->users['mw-' . ($record->friendlyId ?? $record->userId)] = [
                'email' => $record->emailAddress,
                'user' => false,
                'count' => 1
            ];
        }
        if (isset($this->emails[$record->emailAddress])) {
            if (
                $this->emails[$record->emailAddress]->friendlyId != ($record->friendlyId
                        ?? $record->userId)
            ) {
                if (! isset($this->oneemailtwoids[$record->emailAddress])) {
                    $this->oneemailtwoids[$record->emailAddress] = [
                        $this->emails[$record->emailAddress]->friendlyId
                    ];
                }
                $this->oneemailtwoids[$record->emailAddress][] = ($record->friendlyId
                        ?? $record->userId);
            }
            $this->emails[$record->emailAddress] = $this->makeUserTeamSubscription(
                $record,
                $settings,
                $this->emails[$record->emailAddress]
            );

            //$this->output->newLine();
        } else {
            $this->emails[$record->emailAddress] = $this->makeUserTeamSubscription(
                $record,
                $settings
            );




            return;
        }
    }

    public function makeUserTeamSubscription($record, $settings, $existing = false)
    {
        if (! $existing) {
            $user           = new User();
            $user->name     = trim($record->forenames . ' ' . $record->lastName);
            $user->email    = $record->emailAddress;
            $user->company = $record->companyName;
            $user->verified = 1;
            $user->password = bcrypt($record->userId . 'IWishToTest');
            if ($record->friendlyId) {
                $user->friendly_id = $record->friendlyId;
            } else {
                $user->friendly_id = 'GC' . $record->userId;
            }
            $user->admin = 0;
        } elseif (is_object($existing)) {
            $user = $existing;
        }

        if ($settings['brand'] == 'wtr') {
            if (! $user->{$settings['brand'] . '_daily'}) {
                if (isset($record->emailPreferences->HasDaily)) {
                    $user->{$settings['brand'] . '_daily'} = $record->emailPreferences->HasDaily;
                } else {
                    $user->{$settings['brand'] . '_daily'} = false;
                }
            }
        }
        if (! $user->{$settings['brand'] . '_weekly'}) {
            if (isset($record->emailPreferences->HasDaily)) {
                $user->{$settings['brand'] . '_weekly'} = $record->emailPreferences->HasWeekly;
            } else {
                $user->{$settings['brand'] . '_weekly'} = false;
            }
        }


        $user->save();
        $user->brands()->syncWithoutDetaching([$this->brands[$settings['brand']]->id]);
        $ips = new \App\Classes\IpCheck();
        if (isset($record->ipRanges)) {
            foreach ($record->ipRanges as $range) {
                $outrange = $ips->ip2cidr($range->start, $range->end);
                foreach ($outrange as $key => $cdir) {
                    $user->ips()->create([
                        'name' => $range->start . ' - ' . $range->end . ' ' . ($key + 1),
                        'range' => $cdir
                    ]);
                }
            }
        }

        if ($record->groupLicenseNumber && isset($this->grouped[$settings['brand'] . $record->groupLicenseNumber]['team'])) {
            $this->grouped[$settings['brand'] . $record->groupLicenseNumber]['team']->members()->syncWithoutDetaching([
                $user->id]);
            $team = $this->grouped[$settings['brand'] . $record->groupLicenseNumber]['team'];
        } else {
            $team          = new Team();
            $team->user_id = $user->id;
            $team->name    = $record->companyName;
            $team->company = $record->companyName;
            if ($team->name == null) {
                $team->name = $record->lastName;
            }
            //$team->name .= ($record->groupLicenseNumber ? ' - Group sub - '.$record->groupLicenseNumber : ' - Individual sub - '.$record->friendlyId);
            $team->address1 = $record->address1;
            $team->address2 = $record->address2;
            $team->city     = ($record->town ?? '');
            $team->postcode = ($record->code ?? '');
            $team->vat      = $record->vatNumber;

            if (isset($this->country_corrections[$record->country])) {
                $record->country = $this->country_corrections[$record->country];
            }

            if ($record->country) {
                $team->country_id = array_search(
                    $record->country,
                    trans('countries.list')
                );
                if ($team->country_id == false) {
                    dd($record->country);
                }
            } else {
                $team->country_id = '';
            }

            $team->state     = ($record->state ?? '');
            $team->telephone = ($record->phoneNumber ?? '');
            $team->save();
            if ($record->groupLicenseNumber) {
                $this->grouped[$settings['brand'] . $record->groupLicenseNumber]['team']
                    = $team;
            }
            $team->members()->syncWithoutDetaching([$user->id]);
            foreach ($record->Subscriptions as $subscription) {
                if ($record->groupLicenseNumber) {
                    $provider = 'invoice';
                } elseif (isset($this->providers[$subscription->sourceReason])) {
                    $provider = $this->providers[$subscription->sourceReason];
                } else {
                    $provider = 'free';
                }

                $currencys         = [
                    '€' => 'EUR',
                    '$' => 'USD',
                    '£' => 'GBP'
                ];
                $subscription_item = $team->subscriptions()->create([
                    'product_id' => $this->products[$settings['brand']]->id,
                    'start' => Carbon::parse($subscription->startDate),
                    'expiry' => Carbon::parse($subscription->endDate),
                    'payment_details' => json_encode(['reason' => $subscription->sourceReason]),
                    'payment_provider' => $provider,
                    'price' => $subscription->invoiceAmount,
                    'currency' => ! (isset($currencys[$subscription->invoiceCurrency]))
                        ? 'GBP' : $currencys[$subscription->invoiceCurrency],
                    'active' => 1,
                    'type' => $this->products[$settings['brand']]->type
                ]);
            }
            if (isset($subscription_item)) {
                $subscription_item->children()->create([
                    'team_id' => $team->id,
                    'product_id' => $this->products[$settings['brand']]->id,
                    'payment_details' => $subscription_item->payment_details,
                    'payment_provider' => $subscription_item->payment_provider,
                    'start' => $subscription_item->expiry->copy()->addDays(1),
                    'expiry' => $subscription_item->expiry->copy()->addDays(1)->addMinutes($subscription_item->expiry->diffInMinutes($subscription_item->start)),
                    'price' => $record->renewal->rate,
                    'currency' => ! (isset($currencys[$record->renewal->currency]))
                            ? 'GBP' : $currencys[$record->renewal->currency],
                    'active' => 0,
                    'type' => $this->products[$settings['brand']]->type
                ]);
            }
        }


        return $user;
    }
}
