<?php

namespace App\Newsletter;

use App\Exceptions\CampaignNotCreatedException;
use App\Exceptions\CampaignNotScheduledException;
use App\Models\Email;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Arr;
use Newsletter;

class MailchimpClient implements INewsletter
{
    const LIST_NAME = 'subscribers';

    public static $friendlyName = 'Mailchimp';

    public function create(Email $email, Mailable $mailable)
    {
        // Not required for Mailchimp
    }

    public function dispatch(Email $email, Mailable $mailable)
    {
        if (! config('newsletter.apiKey')) {
            throw new \LogicException('The Mailchimp API key is not defined, please configure a testing key');
        }

        $emailHtml = $this->setUnsubscribePlaceholder($mailable->render());

        $campaignResponse = \Spatie\Newsletter\NewsletterFacade::createCampaign(
            $email->brand->email_from['name'],
            $email->brand->email_from['address'],
            $email->subject,
            $emailHtml,
            self::LIST_NAME,
            $this->generateOptions($email)
        );

        if (! $campaignResponse) {
            throw new CampaignNotCreatedException('Campaign failed to be created');
        }

        return [
            'response' => $campaignResponse,
            'url' => $this->campaignAdminUrl($campaignResponse['web_id']),
        ];
    }

    public function schedule($campaignId, $date)
    {
        if (! config('newsletter.apiKey')) {
            throw new \LogicException('The Mailchimp API key is not defined, please configure a testing key');
        }

        $result = \Spatie\Newsletter\NewsletterFacade::getApi()->post("/campaigns/{$campaignId}/actions/schedule", [
            'schedule_time' => $date->toIso8601String(),
            'timewarp' => false,
            'batch_delay' => false,
        ]);

        if ($result['status'] >= 300) {
            throw new CampaignNotScheduledException($result['detail']);
        }
    }

    public function campaignAdminUrl($campaignId)
    {
        // TODO MOVE TO CONFIG -> try to get from Newsletter
        return 'https://us18.admin.mailchimp.com/campaigns/edit?id=' . $campaignId;
    }

    /**
     * @param Email $email
     * @return array
     */
    protected function generateOptions(Email $email)
    {
        $options = ['recipients' => [
            'list_id' => config('newsletter.lists.subscribers.id'),
        ]];

        $segments = $this->segmentsBasedOnEnvironment($email);
        if (! empty($segments)) {
            Arr::set($options, 'recipients.segment_opts', $segments);
        }

        return $options;
    }

    /**
     * We only use segments in the production environment
     * For all other environments we push without any filters
     *
     * @param Email $email
     * @return array
     */
    protected function segmentsBasedOnEnvironment(Email $email)
    {
        if (! app()->environment('production')) {
            return [];
        }

        return [
            'match' => 'any',
            'conditions' => [
                [
                    'condition_type' => 'Interests',
                    // note capital I
                    'field' => 'interests-' . config('newsletter.categories.' . $email->brand->machine_name),
                    // ID of interest category
                    // This ID is tricky: it is
                    // the string "interests-" +
                    // the ID of interest category
                    // that you get from MailChimp
                    // API (31f7aec0ec)
                    'op' => 'interestcontains',
                    // or interestcontainsall, interestcontainsnone
                    'value' => [
                        config('newsletter.interests.' . $email->brand->machine_name . '_' . $email->type),
                    ],
                ],
            ],
        ];
    }

    protected function setUnsubscribePlaceholder($html)
    {
        return str_replace('MAILCHIMP_SUB', '*|UNSUB|*', $html);
    }

    /**
     * @return string
     */
    public static function getFriendlyName()
    {
        return self::$friendlyName;
    }
}
