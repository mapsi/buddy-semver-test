<?php

declare(strict_types=1);

namespace App\Services\Lexology;

use GuzzleHttp\Client;

class Service
{
    protected $client;
    protected $baseUri;
    protected $apiKey;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->baseUri = $baseUri;
        $this->apiKey = $apiKey;

        $this->connect();
    }

    private function connect(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
            ],
        ]);
    }

    public function getToken($reactAppId, $payload = []): string
    {
        return $this->makeRequest('POST', $this->baseUri . $reactAppId, $payload);
    }

    protected function makeRequest($method, $url, $payload = []): string
    {
        $baseUri = $this->baseUri;
        $apiKey = $this->apiKey;

        logger("Making request to '{$url}'", compact('baseUri', 'apiKey', 'method', 'payload'));
        $response = $this->client->request($method, $url, $payload);

        $contents = $response->getBody()->getContents();
        logger('The response was...', compact('contents'));
        $result = json_decode($contents, true);

        return $this->transformResponse($result);
    }

    protected function transformResponse(string $response): string
    {
        //need to trim as response is unnecessary encapsulated
        $response = trim($response, '"');

        return $response;
    }

    public function __sleep(): array
    {
        return ['baseUri', 'apiKey'];
    }

    public function __wakeup(): void
    {
        $this->connect();
    }
}
