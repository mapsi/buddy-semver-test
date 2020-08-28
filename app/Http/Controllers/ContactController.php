<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;

class ContactController extends Controller
{
    protected $forms = [
        'corporate' => [
            'title' => 'SUBSCRIPTION REQUEST',
            'fields' => [
                'title' => 'required',
                'forname' => 'required',
                'surname' => 'required',
                'email' => 'required|email',
                'telephone' => 'required',
                'title_function' => 'required',
                'company' => 'required',
                'country' => 'required',
                'employees' => 'required',
                'terms' => 'required'
            ],
            'success' => 'contact.success',
            'template' => 'emails.admin.subscription'
        ],

        'contact' => [
            'title' => 'CONTACT REQUEST',
            'fields' => [
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                'message' => 'required'
            ],

            'success' => 'contact_us.success',
            'template' => 'emails.admin.contact'
        ]
    ];

    public function corporate()
    {
        return view('contact.corporate');
    }

    public function submit(Request $request, \App\Models\Brand $brand)
    {

        $event = \App\Models\Event\Event::find($request->event);
        $event_type = \App\Models\Event\EventType::find($request->event_type);

        if (! isset($this->forms[$request->form])) {
            return Response()->abort(404, 'Form not found');
        }

        $this->validate($request, $this->forms[$request->form]['fields'] + [ 'g-recaptcha-response' => 'required|captcha' ]);

        $contact = new Contact(
            $request->only(array_keys($this->forms[$request->form]['fields'])),
            $this->forms[$request->form]['title'],
            $this->forms[$request->form]['template'],
            ['event' => $event, 'event_type' => $event_type]
        );

        $mail_to = config('globemedia.notifiy.' . $brand->machine_name . '.' . $request->form);

        if (isset($event)) {
            $mail_to = $event->management_email ? ['contact' => $event->management_email] : config('globemedia.notifiy.' . $brand->machine_name . '.' . $request->form);
        }

        Mail::to($mail_to)->send($contact);

        if (isset($event) && isset($event_type)) {
            $route_params = [
                'event' => $event,
                'event_type' => $event_type
            ];
        }

        return Response()->redirectToRoute($this->forms[$request->form]['success'], $route_params ?? null);
    }

    public function corporateSuccess()
    {
        return view('contact.corporate_success');
    }

    public function contactUsSuccess(\App\Models\Event\EventType $event_type, \App\Models\Event\Event $event)
    {
        return view('events.information-centre.contact-success', ['event' => $event, 'event_type' => $event_type]);
    }
}
