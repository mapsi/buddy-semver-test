<?php

namespace App\Classes;

use DateTime;
use GuzzleHttp\Client as GuzzleClient;

class Drupal
{
    private $config;
    private static $cookies;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return App\Classes\DrupalData
     */
    public function getEntities(string $entity_type, string $bundle = null)
    {
        if (is_null($bundle)) {
            $bundle = $entity_type;
        }
        $this->authenticate(
            $this->config['url'],
            $this->config['username'],
            $this->config['password']
        );
        $data = new DrupalData(
            $this->config['url'] . '/' . $this->config['api_path'] . '/',
            $entity_type,
            $bundle,
            self::$cookies
        );

        return $data;
    }

    public function authenticate(
        string $url,
        string $username,
        string $password,
        string $endpoint = 'user/login'
    ) {
        self::$cookies = new \GuzzleHttp\Cookie\CookieJar();
        //diffrent endpoint
        $client        = new GuzzleClient([
            'base_uri' => $url,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'cookies' => self::$cookies
        ]);
        $client->post(
            $endpoint,
            [
            'query' => [
                '_format' => 'json'
            ],
            'body' => json_encode([
                'name' => $username,
                'pass' => $password
            ])
            ]
        );
    }

    /**
     * @return App\Classes\DrupalData
     */
    public function getContent(string $content_type)
    {
        return $this->getEntities('node', $content_type);
    }

    /**
     * @return App\Classes\DrupalData
     */
    public function getTaxonomyTerms(string $vocabulary)
    {
        return $this->getEntities('taxonomy_term', $vocabulary);
    }

    public function listUnpublishedContent()
    {
        $this->authenticate(
            $this->config['url'],
            $this->config['username'],
            $this->config['password']
        );
        $client = new GuzzleClient(['base_uri' => $this->config['url'], 'cookies' => self::$cookies]);

        $response = $client->get('unpublished-content');

        return json_decode($response->getBody(), true);
    }

    public function getRedirects(DateTime $since = null)
    {
        $this->authenticate(
            $this->config['url'],
            $this->config['username'],
            $this->config['password']
        );
        $client = new GuzzleClient(['base_uri' => $this->config['url'],
            'cookies' => self::$cookies
        ]);

        $page = 0;

        do {
            $response = $client->get(
                'list-redirects',
                [
                    'query' => [
                        'limit' => 50,
                        'page' => $page,
                        'since' => $since ? $since->getTimestamp() - (60 * 60 * 2) : 0,
                    ],
                ]
            );

            $results = json_decode($response->getBody(), true);
            foreach ($results as $result) {
                yield $result;
            }

            $page++;
        } while ($results);
    }
}
