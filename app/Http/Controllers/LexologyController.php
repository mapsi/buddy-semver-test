<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\Lexology\Entities\Horizons;
use App\Services\Lexology\Entities\MandateBrowser;
use App\Services\Lexology\Entities\QaTool;
use App\Services\Lexology\Entities\ReportsCentre;
use App\Services\Lexology\Entities\VerticalRestraints;
use App\Services\Lexology\Entities\WorkAreaLanding;
use App\Services\Lexology\Service;

class LexologyController extends Controller
{
    public function gtdtLanding(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.gtdt.workarea_landing_key');
        $client = new Service($baseUri, $apiKey);

        $entity = new WorkAreaLanding($client, $brand);

        return view('gtdt/show', compact('entity'));
    }

    public function gdtdQaTool(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.gtdt.qatool_key');
        $client = new Service($baseUri, $apiKey);

        $entity = new QaTool($client, $brand);

        return view('gtdt/show', compact('entity'));
    }
    public function mandates(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.mandate_browser_key');
        $client = new Service($baseUri, $apiKey);

        $entity = new MandateBrowser($client, $brand);

        return view('mandates/show', compact('entity'));
    }

    public function horizons(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.horizonsapi');
        $client = new Service($baseUri, $apiKey);

        $entity = new Horizons($client, $brand);

        return view('horizons/show', compact('entity'));
    }

    public function reportsCentre(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.reportscentreapi');
        $client = new Service($baseUri, $apiKey);

        $entity = new ReportsCentre($client, $brand);

        return view('reports-centre/show', compact('entity'));
    }

    public function verticalRestraints(Brand $brand)
    {
        $baseUri = env('LEXOLOGY_API_URL');
        $apiKey = get_host_config('services.vertical_restraints');
        $client = new Service($baseUri, $apiKey);

        $entity = new VerticalRestraints($client, $brand);

        return view('vertical-restraints/show', compact('entity'));
    }
}
