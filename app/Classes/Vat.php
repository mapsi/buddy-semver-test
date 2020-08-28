<?php

namespace App\Classes;

use GuzzleHttp\Client;

/**
 * Description of Vat
 *
 * @author mark
 */
class Vat
{
    protected $client;
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://gbmgp360vatapi.azurewebsites.net/',
            'http_errors' => false,
            'headers' => [

                'Api-Key' => 'b1d2d929-da24-4e12-8e6e-746f6dfaa76c'
            ],
        ]);
    }
    public function getVat($country, $state, $company_name, $vatnumber)
    {
        $data = [
            'BillingCountry' => "GB",
            'BillingState' => '',
            'BuyerCountry' => $country,
            'BuyerState' => '',
            'ProductCategory' => 'Subscription',
            'ProductType' => "Digital",
            'LegalEntityName' => $company_name,
            'SupplyCountry' => "GB",
            'SupplyState' => '',
            'VatNumber' => $vatnumber
        ];
        try {
            $r = $this->client->post('/api/vat/rates', [
                'form_params' => $data
            ]);
            $ratedata = json_decode($r->getBody());


            if ($ratedata && isset($ratedata->Rate)) {
                return $ratedata->Rate;
            } else {
                debug((string) $r->getBody());
                debug($data);
            }
            return 0;
        } catch (\Exception $e) {
            debug($e->getMessage());
        }
    }
}
