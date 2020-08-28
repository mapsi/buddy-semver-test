<?php

namespace App\Classes;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class WtrDirectoryApi
{
    protected $client;
    protected $preview;
    public function __construct(string $base_uri)
    {
        $this->client = new Client([
            'base_uri' => $base_uri,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function individuals()
    {
        $response = $this->client->get('individuals');

        return json_decode($response->getBody(), true);
    }

    public function firms()
    {
        $response = $this->client->get('firms');

        return json_decode($response->getBody(), true);
    }

    public function jurisdictions()
    {
        $response = $this->client->get('jurisdictions');

        return json_decode($response->getBody(), true);
    }

    public function rankings(string $jurisdiction_uuid)
    {
        $response = $this->client->get('rankings(' . $jurisdiction_uuid . ')');

        return json_decode($response->getBody(), true);
    }
}
