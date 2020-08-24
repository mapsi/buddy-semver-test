<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;

class ViewerMetaDataController
{
    /**
     * @param Article $article
     * @return array
     */
    public function __invoke(Request $request)
    {
        if ($request->header('ApiKey') !== 'dx9e8VHgYx8MfFEc9tt3') {
            abort(403);
        }

        $userId = $request->get('userId');

        return cacheStuff(__METHOD__ . $userId, 60, function () use ($userId) {
            $user = User::findOrFail($userId);
            $lbrDetail = $user->lbrDetail;

            return [
                'CompanyName' => $user->company,
                'CommonId' => $user->friendly_id,
                'LbrOrganisationId' => $lbrDetail ? $lbrDetail->lbr_organisation_id : null,
                'LbrAccountId' => $lbrDetail ? $lbrDetail->lbr_account_id : null,
            ];
        });
    }
}
