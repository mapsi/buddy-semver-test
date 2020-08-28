<?php

namespace App\Newsletter;

use App\Models\Email;
use App\Models\EmailCommunigator;
use Illuminate\Contracts\Mail\Mailable;

class CommunigatorClient implements INewsletter
{
    private $cgClient;

    private $campaignId;

    private $campaignStartDate;

    private $campaignEndDate;

    public static $friendlyName = 'Communigator';

    /**
     * @param Email    $email
     * @param Mailable $mailable
     * @return array|false
     * @throws \Exception
     */
    public function create(Email $email, Mailable $mailable)
    {
        $this->cgClient = new EmailCommunigator();

        $data = [
            'name' => $email->brand->email_from_newsletter['name_' . $email->type],
            'address' => $email->brand->email_from_newsletter['address'],
            'subject' => $email->subject,
            'machine' => $email->brand->machine_name,
            'type' => $email->type,
            'scheduled_for' => $email->scheduled_for ?: null,
            'body' => $mailable->render(),
        ];

        $response = $this->cgClient->createCommuniGatorCampaign($data);

        $this->campaignId = $response['response']->id;
        $this->campaignStartDate = $response['start_date'];
        $this->campaignEndDate = $response['end_date'];

        return $response;
    }

    /**
     * @param Email    $email
     * @param Mailable $mailable
     * @return array|bool
     * @throws \Exception
     */
    public function dispatch(Email $email, Mailable $mailable)
    {
        $this->create($email, $mailable);

        $data = [
            'id' => $this->campaignId,
        ];

        // Initiate the campaign
        $response = $this->cgClient->launchCommunigatorCampaign($data);

        if ($response->campaignInitiated) {
            flash('Email sent to CommuniGator as a new campaign')->success();

            return [
                'response' => $response,
                'url' => 'https://gatormail.communigator.co.uk/default.aspx?tabid=65&itemId=' . $this->campaignId,
            ];
        }

        flash('CommuniGator can\'t launch the existing campaign: ' . $response->message)->error();

        return false;
    }

    /**
     * @param $campaignId
     * @param $date
     */
    public function schedule($campaignId, $date)
    {
        // Not required for Communigator
    }

    /**
     * @return string
     */
    public static function getFriendlyName()
    {
        return self::$friendlyName;
    }
}
