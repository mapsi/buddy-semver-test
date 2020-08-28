<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event\Event;
use App\Models\Event\Speaker;
use App\Models\Event\Sponsor;
use App\Models\Event\PreviousYear;
use App\Models\Event\Gallery;
use Illuminate\View\View;
use App\Models\Event\EventType;
use App\Mail\Contact;
use Illuminate\Support\Facades\Mail;
use App\Events\ContentViewed;

class EventController extends Controller
{
    public function languageRedirect($language, EventType $event_type)
    {

        return $this->redirect($event_type);
    }

    public function redirect(EventType $event_type)
    {
        return redirect()->route('events.index', ['event_type' => $event_type,'event' => $event_type->events()->orderBy('date_start', 'desc')->first()]);
    }

    public function languageIndex($language, EventType $event_type, Event $event)
    {
        return $this->index($event_type, $event);
    }

    public function index(EventType $event_type, Event $event)
    {
        $event->load('sponsors.media');
        return view('events.homepage', ['event' => $event,'event_type' => $event_type]);
    }

    public function languageAbout($language, EventType $event_type, Event $event)
    {
        debug($event);
        return $this->about($event_type, $event);
    }

    public function about(EventType $event_type, Event $event)
    {
        $event->load('sponsors.media');
        return view('events.about', ['event' => $event,'event_type' => $event_type]);
    }

    public function languageAboutSection($language, EventType $event_type, Event $event, $section)
    {
        return $this->aboutSection($event_type, $event, $section);
    }

    public function aboutSection(EventType $event_type, Event $event, $section)
    {
        $event->load('sponsors.media');

        if (view()->exists('events.about.' . $section)) {
            return view('events.about.' . $section, ['event' => $event,'event_type' => $event_type, 'section' => $section]);
        }

        return view('events.about', ['event_type' => $event_type,'event' => $event]);
    }

    public function languageSection($language, EventType $event_type, Event $event, $section)
    {
        return $this->section($event_type, $event, $section);
    }

    public function section(EventType $event_type, Event $event, $section)
    {
       // $event->load('sponsors.media');
        if (view()->exists('events.' . $section)) {
            return view('events.' . $section, ['event' => $event,'event_type' => $event_type, 'section' => $section]);
        }

        return view('events.homepage', ['event_type' => $event_type,'event' => $event]);
    }


    public function languageInformationCentreSection($language, EventType $event_type, Event $event, $section)
    {
        return $this->informationCentreSection($event_type, $event, $section);
    }

    public function informationCentreSection(EventType $event_type, Event $event, $section)
    {
        $event->load('sponsors.media');

        if (view()->exists('events.information-centre.' . $section)) {
            return view('events.information-centre.' . $section, ['event' => $event,'event_type' => $event_type, 'section' => $section]);
        }

        return view('events.information-centre', ['event' => $event,'event_type' => $event_type]);
    }

    public function languageResourceSection($language, EventType $event_type, Event $event, $section)
    {
        return $this->resourceSection($event_type, $event, $section);
    }

    public function resourceSection(EventType $event_type, Event $event, $section)
    {
        $event->load('sponsors.media');

        if (view()->exists('events.resources.' . $section)) {
            return view('events.resources.' . $section, ['event' => $event,'event_type' => $event_type, 'section' => $section]);
        }

        return view('events.resources', ['event' => $event, 'event_type' => $event_type]);
    }

    public function languageSpeaker($language, EventType $event_type, Event $event, Speaker $speaker)
    {
        return $this->speaker($event_type, $event, $speaker);
    }

    public function speaker(EventType $event_type, Event $event, Speaker $speaker)
    {
        return view('events.speaker', ['event' => $event,'event_type' => $event_type, 'speaker' => $speaker]);
    }

    public function languageSponsor($language, EventType $event_type, Event $event, Sponsor $sponsor)
    {
        return $this->sponsor($event_type, $event, $sponsor);
    }

    public function sponsor(EventType $event_type, Event $event, Sponsor $sponsor)
    {
        return view('events.sponsor', ['event' => $event,'event_type' => $event_type, 'sponsor' => $sponsor]);
    }

    public function languagePreviousYearFind($language, EventType $event_type, Event $event)
    {
        return $this->previousYearFind($event_type, $event);
    }

    public function previousYearFind(EventType $event_type, Event $event)
    {
        $event->load('sponsors.media');
        if ($previousYear = $event->previousYears->sortByDesc('year')->first()) {
            return $this->previousYear($event_type, $event, $previousYear);
        }

        return view('events.homepage', ['event' => $event,'event_type' => $event_type]);
    }

    public function languagePreviousYear($language, EventType $event_type, Event $event, PreviousYear $previous_year)
    {
        return $this->previousYear($event_type, $event, $previous_year);
    }

    public function previousYear(EventType $event_type, Event $event, PreviousYear $previous_year)
    {
        $event->load('sponsors.media')->load('sponsorsAll.media');
        return view('events.about.previous-year', ['event' => $event,'event_type' => $event_type, 'previous_year' => $previous_year]);
    }

    public function languagePreviousYearGallery($language, EventType $event_type, Event $event, PreviousYear $previous_year, Gallery $gallery)
    {
        return $this->previousYearGallery($event_type, $event, $previous_year, $gallery);
    }

    public function previousYearGallery(EventType $event_type, Event $event, PreviousYear $previous_year, Gallery $gallery)
    {
        $event->load('sponsors.media')->load('sponsorsAll.media');

        return view('events.about.previous-year', [
            'event' => $event,
            'event_type' => $event_type,
            'previous_year' => $previous_year,
            'gallery' => $gallery,
        ]);
    }

    public function languageGetContentGate($language, EventType $event_type, Event $event, Request $request)
    {
        return $this->getContentGate($event_type, $event, $request);
    }

    public function getContentGate(EventType $event_type, Event $event, Request $request)
    {
        $passed = $event->getGateValues($event, $event_type);
        $user = auth()->user();

        if ($user) {
            $team = $user->teams->first();
            $contact = new Contact([
                'title' => $user->title,
                'forename' => $user->forename,
                'surname' => $user->surname,
                'company' => $user->company,
                'title_function' => $user->job_function,
                'telephone' => $team->telephone,
                'country' => $team->country_id,
                'email' => $user->email,
                'user' => 'Registered user',
            ], 'Event registation', 'emails.admin.contact', ['event' => $event, 'event_type' => $event_type]);
            Mail::to($event->management_email)->send($contact);
        }

        if (auth()->user() || $request->cookie($passed['key']) == $passed['value']) {
            $response = response()->view('events.contentgate.success', [
                'download' => route('events.gate_download', [$event_type,$event,'signed' => $request->get('signed'),'download' => $request->get('download')])
            ]);

            if (auth()->user()) {
                $response->withCookie(cookie()->forever($passed['key'], $passed['value']));
            }

            return $response;
        }
        return  view('events.contentgate.gate', ['event' => $event,'event_type' => $event_type,'signed' => $request->get('signed'),'download' => $request->get('download')]);
    }

    public function languagePostContentGate($language, EventType $event_type, Event $event, Request $request)
    {
        return $this->postContentGate($event_type, $event, $request);
    }

    public function postContentGate(EventType $event_type, Event $event, Request $request)
    {
        $fields = [
            'title' => 'required',
            'forename' => 'required',
            'surname' => 'required',
            'company' => 'required',
            'title_function' => 'required',
            'telephone' => 'required',
            'country' => 'required',
            'email' => 'required|email',

        ];

        $this->validate($request, $fields + [
            'terms' => 'required',
            'g-recaptcha-response' => app()->environment(['local', 'testing', 'production']) ? '' : 'required|captcha',
        ]);

        $response = response()->view('events.contentgate.success', ['download' => route('events.gate_download', [$event_type,$event,'signed' => $request->get('signed'),'download' => $request->get('download')])]);
        $passed = $event->getGateValues($event, $event_type);
        $response->withCookie(cookie()->forever($passed['key'], $passed['value']));

        $contact = new Contact($request->only(array_keys($fields)), 'Event registation', 'emails.admin.contact', ['event' => $event, 'event_type' => $event_type]);
        Mail::to($event->management_email)->send($contact);
        return $response;
    }

    public function languageGetDownloadFile($language, EventType $event_type, Event $event, Request $request)
    {
        return $this->getDownloadFile($event_type, $event, $request);
    }

    public function getDownloadFile(EventType $event_type, Event $event, Request $request)
    {
        $passed = $event->getGateValues($event, $event_type);

        $media = \App\Models\Media::findOrFail($request->get('download'));
        $shouldallow = ($request->cookie($passed['key']) == $passed['value'] || ! $media->model->data['gated']);

        if (($shouldallow || auth()->user()) &&  $media->signature == $request->get('signed')) {
            if (method_exists($media->model, 'views')) {
                event(new ContentViewed($media->model));
            }

            return $media->toResponse($request);
        } else {
            abort(403);
        }
    }
}
