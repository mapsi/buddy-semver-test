<?php

namespace App\Console\Commands;

use App\Classes\Export as baseExport;
use App\Models\User;
use App\Models\View;
use App\Models\Log;

class Export extends baseExport
{

    protected function modeUsers()
    {
        $users = User::select()->with('teams.subscriptions.product.brands');
        $users->with('teams.members');
        $brands = \App\Models\Brand::get();
        if (isset($this->filters['start'])) {
            $users->where('created_at', '>=', $this->filters['start']);
        }
        if (isset($this->filters['end'])) {
            $users->where('created_at', '<=', $this->filters['end']);
        }
        $this->makeCsv($users, function ($user) use ($brands) {
            $return = [
                'Forename' => $user->forename,
                'Surname' => $user->surname,
                'Friendly id' => $user->friendly_id,
                'Email' => $user->email,
                'WTR Weekly email' => $user->wtr_weekly,
                'WTR Daily email' => $user->wtr_daily,
                'IAM Weekly email' => $user->iam_weekly,
                'Created at' => $user->created_at,
            ];
            foreach (
                [
                'wtr_daily' => 'WTR Daily email',
                'wtr_weekly' => 'WTR Weekly email',
                'iam_weekly' => 'IAM Weekly email'
                ] as $newsletter => $brand
            ) {
                $l = Log::where('loggable_id', '=', $user->id)->where('loggable_type', User::class)
                    ->where('type', '=', $user->$newsletter ? 'optin' : 'optout')->orderBy('created_at', 'desc')->first();
                if ($l) {
                    $return['Date for ' . $brand] = $l->created_at;
                } else {
                    $return['Date for ' . $brand] = $user->created_at;
                }
            }
            foreach ($brands as $brand) {
                $return['Registered for ' . $brand->name] = $user->brands->filter(function ($ubrand) use ($brand) {
                    return $ubrand->id == $brand->id;
                })->count() != 0;
            }
            foreach ($user->teams as $team) {
                foreach ($brands as $brand) {
                    $return += $this->userSubscription($team, $brand);
                }
            }
            return $return;
        });
    }
    protected function modeUsage()
    {
        $query = View::select()->where('type', 'read');
        $query->with('user');
        $query->with('routable.brand');
        $query->with('routable.articleTypes');
        $wtr = \App\Models\Brand::findByMachineName('wtr');
        $iam = \App\Models\Brand::findByMachineName('iam');
        if (isset($this->filters['start'])) {
            $query->where('created_at', '>=', $this->filters['start']);
        }
        if (isset($this->filters['end'])) {
            $query->where('created_at', '<=', $this->filters['end']);
        }
        $this->makeCsv($query, function ($view) use ($wtr, $iam) {

            $return = [
                'User' => $view->user ? $view->user->email : '',
                'Full name' => $view->user ? $view->user->name : '',
                'Company' => $view->user ? $view->user->company : '',
                'User ID' => $view->user_id,
                'Is subscriber WTR' => $view->user ? $view->user->isSubscriberOfBrand($wtr) : '',
                'Is subscriber IAM' => $view->user ? $view->user->isSubscriberOfBrand($iam) : '',
                'Is full read' => $view->is_full_read,
                'Is Free' => $view->is_free,
                'Device' => $view->device,
                'Platform' => $view->platform,
                'Browser' => $view->browser,
                'Browser version' => $view->version,
                'Is desktop' => $view->is_desktop,
                'Is phone' => $view->is_phone,
                'Is robot' => $view->is_robot,
                'IP' => $view->ip,
                'Date read' => $view->created_at,
                'Content CMS id' => $view->routable ? ($view->routable->import ? $view->routable->import->entity_id : 'Deleted')  : '',
                'Content CMS link' => $view->routable ? ($view->routable->import ? config('globemedia.drupal.url') . '/node/' . $view->routable->import->entity_id . '/edit' : '')  : '',
                'Content title' => $view->routable ? ($view->routable->import && $view->routable->title ? $view->routable->title : '')  : '',
                'Content types' => '',
                'Published at' => $view->routable ? ($view->routable->created_at ?? '') : ''
            ];
            if ($view->routable instanceof \App\Models\Interfaces\Publishable) {
                $return['Published at'] = $view->routable->published_at;
            }
            if ($view->routable instanceof \App\Models\Article) {
                $return['type'] = $view->routable->articleTypes->map(function ($item) {
                    return $item->name;
                })->sort()->implode(', ');
                $return['Brand'] = $view->routable->brand->name;
            }
            return $return;
        });
    }

    private function userSubscription($team, $brand)
    {
        $sub = $team->subscriptions->filter(function ($item) use ($brand) {
            return $item->product->brands->filter(function ($item) use ($brand) {
                return $item->id == $brand->id;
            })->count();
        });
        //billing_address1
        $return = [
            $brand->title . ' team name'  => '',
            $brand->title . ' team is a group'  => '',

            $brand->title . ' address1' => null,
            $brand->title . ' address2' => null,
            $brand->title . ' address3' => null,
            $brand->title . ' city' => null,
            $brand->title . ' state' => null,
            $brand->title . ' country' => null,
            $brand->title . ' postcode'  =>    null,

            $brand->title . ' billing address1' => null,
            $brand->title . ' billing address2' => null,
            $brand->title . ' billing address3' => null,
            $brand->title . ' billing city' => null,
            $brand->title . ' billing state' => null,
            $brand->title . ' billing country' => null,
            $brand->title . ' billing postcode'  =>   null,

            $brand->title . ' subscription start' => null,
            $brand->title . ' subscription end' => null,

            $brand->title . ' subscription renewal currency' => null,
            $brand->title . ' subscription renewal' => null,
        ];
        if ($sub->isNotEmpty()) {
            $return = [
                $brand->title . ' team name'  => $team->name,
                $brand->title . ' team is a group'  => 0,

                $brand->title . ' address1' => $team->address1,
                $brand->title . ' address2' => $team->address2,
                $brand->title . ' address3' => $team->address3,
                $brand->title . ' city' => $team->city,
                $brand->title . ' state' => $team->state,
                $brand->title . ' country' => ($team->country_id ? trans('countries.list.' . $team->country_id)
                    : ''),
                $brand->title . ' postcode'  =>    $team->postcode,

                $brand->title . ' billing address1' => $team->billing_address1,
                $brand->title . ' billing address2' => $team->billing_address2,
                $brand->title . ' billing address3' => $team->billing_address3,
                $brand->title . ' billing city' => $team->billing_city,
                $brand->title . ' billing state' => $team->billing_state,
                $brand->title . ' billing country' => ($team->billing_country_id ? trans('countries.list.' . $team->billing_country_id)
                    : ''),
                $brand->title . ' billing postcode'  =>    $team->billing_postcode,

                $brand->title . ' subscription start' => null,
                $brand->title . ' subscription end' => null,

                $brand->title . ' subscription renewal currency' => null,
                $brand->title . ' subscription renewal' => null,
            ];
            $active = $sub->where('active', 1);
            $return[ $brand->title . ' subscription end' ] = $sub->first()->product->name;
            if ($active->isNotEmpty()) {
                $return[ $brand->title . ' team is a group' ] =  $team->members->count() != 1;
                $return[ $brand->title . ' subscription start' ] = $active->sortBy('start')->first()->start;
                $return[ $brand->title . ' subscription end' ] = $active->sortByDesc('expiry')->first()->expiry;
            }
            $inactive = $sub->where('active', 0);
            if ($inactive->isNotEmpty()) {
                $return[$brand->title . ' subscription renewal currency'] = $inactive->sortByDesc('start')->first()->currency;
                $return[$brand->title . ' subscription renewal'] = $inactive->sortByDesc('start')->first()->price;
            }
        }
        return $return;
    }
}
