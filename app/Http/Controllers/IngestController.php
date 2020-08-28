<?php

namespace App\Http\Controllers;

class IngestController extends Controller
{
    public function __invoke(string $sourceType, string $uuid)
    {
        session(['preview' => true]);

        // we need to get the service like this, otherwise it will not have the right key to enable the ingestion.
        $service = brandService();

        $content =  $service->ingest($sourceType, $uuid);

        return redirect(url('') . $content->getCanonicalUrl());
    }
}
