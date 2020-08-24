<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;

class AuditController extends Controller
{
    /**
     * @var array
     */
    protected $brands;

    /**
     * @var array
     */
    protected $types = [
        0 => 'GBMG.Audit.WebFormAuditEvent, GBMG.Audit',
        1 => 'GBMG.Audit.AuditEvent, GBMG.Audit',
        2 => 'GBMG.Audit.SubscriptionEvent, GBMG.Audit',
        3 => 'GBMG.Audit.AuditEvent, GBMG.Audit',
        4 => 'GBMG.Audit.AuditProductSiteVisitEvent, GBMG.Audit',
    ];

    /**
     * @return void
     */
    public function __construct()
    {
        $this->brands = config('globemedia.events.ids');
    }

    /**
     * @return bool
     */
    protected function allowed()
    {
        if (Auth()->user() && Auth()->user()->admin) {
            return true;
        }
        if (request()->header('ApiKey') == config('globemedia.events.api_key')) {
            return true;
        }
        abort(403);
    }

    /**
     * @return array
     */
    public function index()
    {
        $this->allowed();
        $out = [
            '$type' => "GBMG.Audit.AuditEvent[], GBMG.Audit",
            '$values' => [],
        ];
        foreach (AuditEvent::limit(request()->input('max', 20))->get() as $event) {
            $out['$values'][] = $this->formatResponse($event);
        }

        return $out;
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $this->allowed();

        //just returns the count nothing else
        return AuditEvent::count();
    }
    ///needs rewrite for this

    /**
     * @param $eventuuid
     */
    public function delete($eventuuid)
    {
        $event = AuditEvent::where('uuid', '=', $eventuuid)->first();
        if (! $event) {
            abort(404);
        }
        $this->allowed();
        $event->delete();
    }

    /**
     * @param AuditEvent $event
     * @return \stdClass
     */
    protected function formatResponse(AuditEvent $event)
    {
        $item = new \stdClass();
        $item->{'$type'} = $this->types[$event->type];
        $brand = \App\Models\Brand::find($event->brand_id);
        //same but just diffent codes
        $item->EventType = $event->type + $this->brands[$brand->machine_name];

        $item->EventId = $event->uuid;
        $item->EventDateTime = $event->created_at->format('c');
        $item->DateCreatedDateTime = $event->created_at->format('c');
        $item->ManuallyInputted = false;
        $item->ManuallyInputtedBy = null;
        $item->UserId = $event->user_id;
        if ($event->user) {
            $item->EmailAddress = $event->user->email;
            $names = explode(' ', $event->user->name);
            if (count($names) >= 1) {
                $item->FirstName = array_shift($names);
                $item->LastName = implode(' ', $names);
            } else {
                $item->FirstName = '';
                $item->LastName = $event->user->name;
            }
        } else {
            $item->EmailAddress = 'User deleted';
        }
        if (method_exists($this, 'format' . $event->type)) {
            $this->{'format' . $event->type}($event, $item);
        }

        return $item;
    }

    /**
     * @param AuditEvent $event
     * @param \stdClass  $default
     */
    protected function format0(AuditEvent $event, \stdClass $default)
    {
        $default->FormKeys = implode(',', json_decode($event->data)->form);
    }

    /**
     * @param AuditEvent $event
     * @param \stdClass  $default
     */
    protected function format4(AuditEvent $event, \stdClass $default)
    {
        $default->DescriptionOfPeriod = json_decode($event->data)->period;
    }

    /**
     * @param AuditEvent $event
     * @param \stdClass  $default
     */
    protected function format2(AuditEvent $event, \stdClass $default)
    {
        $data = json_decode($event->data);
        $start = new \Carbon\Carbon($data->dates->start->date, $data->dates->start->timezone);
        $default->StartDate = $start->format('c');
        $end = new \Carbon\Carbon($data->dates->expiry->date, $data->dates->expiry->timezone);
        $default->EndDate = $end->format('c');
        if (isset($event->data->form)) {
            $default->FormKeys = implode(',', json_decode($event->data->form));
        } else {
            $default->FormKeys = '';
        }
    }
}
