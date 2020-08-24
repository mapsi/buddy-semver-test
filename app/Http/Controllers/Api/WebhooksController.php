<?php

namespace App\Http\Controllers\Api;

use App\Jobs\UpdateContent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WebhooksController extends Controller
{
    public function cms(Request $request)
    {
        if ($request->input('messages')) {
            foreach ($request->input('messages') as $message) {
                UpdateContent::dispatch($message);
            }
        }

        return 'ok';
    }
}
