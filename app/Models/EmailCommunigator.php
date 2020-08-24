<?php

namespace App\Models;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class EmailCommunigator
{
    const TIMEOUT = 10;

    private $apiEndpoint;

    private $campaignEndpoint;

    private $userEndpoint;

    private $tokenEndpoint;

    private $landingZone;

    private $accessToken;

    /**
     * Create a new instance
     *
     * @param string $landingZone Your CommuniGator zone
     * @param string $apiEndpoint Optional custom API endpoint
     * @throws \Exception
     */
    public function __construct($landingZone = null, $apiEndpoint = null)
    {
        $this->apiEndpoint = config('newsletter.providers.communigator.api_endpoint');
        $this->landingZone = ($landingZone !== null) ? $landingZone : config('newsletter.providers.communigator.landing_zone');
        $this->campaignEndpoint = ($apiEndpoint !== null) ? $apiEndpoint : $this->apiEndpoint . $this->landingZone . '/campaign';
        $this->tokenEndpoint = config('newsletter.providers.communigator.token_endpoint');
        $this->userEndpoint = $this->apiEndpoint . $this->landingZone . '/contact/';
    }

    /**
     * @param $data
     * @return array|false
     * @throws \Exception
     */
    public function createCommuniGatorCampaign($data)
    {
        $response = $this->getCommuniGatorToken();

        if (array_key_exists('access_token', $response)) {
            $this->accessToken = $response->access_token;

            return $this->createCommuniGatorCampaignWithToken($data);
        }

        throw new \Exception("Token unavailable");
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function createCommuniGatorCampaignWithToken($data)
    {
        $headers = [
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
            'api-version' => config('newsletter.providers.communigator.api_version'),
        ];

        $scheduledFor = $data['scheduled_for'];
        $startDate = $endDate = null;

        if ($scheduledFor) {
            $startDate = $scheduledFor->format('Y-m-d\TH:i:s');
            $endDate = $scheduledFor->copy()->addWeek()->format('Y-m-d\TH:i:s');
        }

        $args = [
            'id' => '',
            'name' => $this->formatName($data),
            'crmId' => '',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'created' => $this->applyTimezoneOrNull(Carbon::now())->format('Y-m-d\TH:i:s'),
            'replyTo' => '',
            'fromName' => $data['name'],
            'fromAddress' => $data['address'],
            'subject' => $data['subject'],
            'type' => 'Static',
            // "Static" "RefreshRecurring" "RefreshNonRecurring" "StaticRecurring" "Followup"

            'group' => [
                'name' => config('newsletter.providers.communigator.group_list_name'),
                'description' => '',
                'created' => '',
            ],

            'email' => [
                'id' => '',
                'name' => $data['subject'],
                'html' => $data['body'],
                'text' => strtoupper($data['machine']),
                'autoTrackLinks' => true,
            ],
        ];

        //append brand-specific receipients group
        $args['group']['id'] = config('newsletter.providers.communigator.group_id.' . $data['machine'] . '_' . $data['type']);

        if ($args['group']['id'] == null) {
            throw new \Exception('Receipients group for ' . $data['machine'] . ' not specified');
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'response' => $this->makeRequest('post', $this->campaignEndpoint, $headers, $args),
        ];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function launchCommunigatorCampaign($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Length' => '0',
            'api-version' => config('newsletter.providers.communigator.api_version'),
        ];

        $launchEndpoint = "{$this->campaignEndpoint}/{$data['id']}/initiate";

        return $this->makeRequest('post', $launchEndpoint, $headers);
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function getCommuniGatorToken()
    {
        $encodedClientSecret = base64_encode(sprintf(
            "%s:%s",
            config('newsletter.providers.communigator.client_id'),
            config('newsletter.providers.communigator.client_secret')
        ));

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . $encodedClientSecret,
        ];

        $args = [
            'grant_type' => 'password',
            'username' => config('newsletter.providers.communigator.sso_username'),
            'password' => config('newsletter.providers.communigator.sso_password'),
            'scope' => 'openid GatorLeadsApi GatorMailApi',
        ];

        return $this->makeTokenRequest('post', $this->tokenEndpoint, $headers, $args);
    }

    /**
     * @param       $action
     * @param       $url
     * @param       $headers
     * @param array $args
     * @return mixed
     */
    private function makeTokenRequest($action, $url, $headers, array $args = [])
    {
        $client = new Client();
        $response = $client->$action($url, [
            'form_params' => $args,
            'headers' => $headers,
        ]);

        return $this->parseJsonResponse($response->getBody()->getContents());
    }

    /**
     * @param       $action
     * @param       $url
     * @param       $headers
     * @param array $args
     * @return mixed
     */
    private function makeRequest($action, $url, $headers, $args = [])
    {
        $client = new Client();
        $response = $client->$action($url, [
            'body' => json_encode($args),
            'headers' => $headers,
        ]);

        return $this->parseJsonResponse($response->getBody()->getContents());
    }

    /**
     * @param $jsonString
     * @return mixed
     */
    private function parseJsonResponse(string $jsonString)
    {
        return json_decode($jsonString);
    }

    /**
     * [brand shortcode]-dd/mm/yy-HH:mm:ss - [newsletter subject]
     *
     * @param $data
     * @return string
     */
    protected function formatName($data)
    {
        $machine = Arr::get($data, 'machine') . '_' . Arr::get($data, 'type');
        $scheduledFor = optional(Arr::get($data, 'scheduled_for'))->format('Y-m-d H:i:s');
        $subject = Arr::get($data, 'subject');

        return sprintf('[%s] - %s - [%s]', $machine, $scheduledFor, $subject);
    }

    /**
     * @param Carbon $scheduled_for
     * @return Carbon|null
     */
    protected function applyTimezoneOrNull($scheduled_for)
    {
        return $scheduled_for
            ? $scheduled_for->setTimezone($this->timezone())
            : null;
    }

    /**
     * @return array|\Illuminate\Config\Repository|mixed
     */
    protected function timezone()
    {
        return config('newsletter.providers.communigator.timezone', 'GMT');
    }
}
