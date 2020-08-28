<?php

namespace App\Http\Controllers;

use App\Services\ContentApi\Entities\Firm;
use App\Services\ContentApi\TagGroups\FirmContributorType;

class DailyController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show()
    {
        $brand = active_host();
        if ($brand !== 'wtr') {
            abort(404);
        }

        $data = $this->cacheResponseData(function () {
            $service = hackyConfigService();

            $search = $service->newSearch();
            $search
                ->setTagIds([FirmContributorType::TAG_ID_WTR_DAILY_CONTRIBUTOR])
                ->setPageSize(1000);

            $firms = $service->run($search)->hydrate();

            $groups = $firms->mapToGroups(function (Firm $firm) {
                return [$firm->getInfo('Country') => $firm];
            })->sortKeys();

            return compact('groups');
        });

        return view('daily.show', $data);
    }
}
