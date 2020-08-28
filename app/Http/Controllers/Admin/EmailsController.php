<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Classes\EmailPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEmailRequest;
use App\Http\Requests\Admin\SaveEmailRequest;
use App\Http\Requests\Admin\SendEmailRequest;
use App\Mail\SubscriptionEmail;
use App\Models\Brand;
use App\Models\Email;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EmailsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $emails = Email::latest()->paginate();

        return view('emails.index', compact('emails'));
    }

    /**
     * @param string       $brandMachineName
     * @param string       $type
     * @param EmailService $service
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request, string $brandMachineName, string $type, EmailService $service)
    {
        $data = $service->getData($brandMachineName, $type);

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('emails.create', $data);
    }

    /**
     * @param SaveEmailRequest $request
     * @param Email            $email
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public function store(SaveEmailRequest $request, Email $email)
    {
        $data = $request->all();

        $brandSlug = Arr::get($data, 'brand');
        $brand = Brand::findByMachineNameOrFail($brandSlug);

        $email = $email->fill([
            'type' => Arr::get($data, 'type'),
            'subject' => Arr::get($data, 'subject'),
            'scheduled_for' => Arr::get($data, 'scheduled_for'),
            'content' => Arr::except($request->validated(), ['subject']),
        ]);

        $email->forBrand($brand)->save();

        flash('Email created', 'success');

        if ($request->ajax()) {
            return [
                'status' => 'success',
                'id' => $email->id,
            ];
        }

        return redirect()->route('emails.show', $email);
    }

    /**
     * @param Email $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Email $email)
    {
        return view('emails.show', compact('email'));
    }

    /**
     * @param Email        $email
     * @param EmailService $service
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Email $email, EmailService $service)
    {
        $data = $service->getData($email->brand->machine_name, $email->type, $email);
        $data['brand'] = $email->brand->machine_name;
        $data['emailType'] = $email->type;

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('emails.create', $data);
    }

    /**
     * @param Email $email
     * @return array
     */
    public function getEmailContent(Email $email)
    {
        return array_merge(
            ['subject' => $email->subject, 'email_id' => $email->id],
            $email->content
        );
    }

    /**
     * @param SaveEmailRequest $request
     * @param Email            $email
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function update(SaveEmailRequest $request, Email $email)
    {
        $email->update([
            'scheduled_for' => $request->input('scheduled_for'),
            'subject' => $request->input('subject'),
            'content' => Arr::except($request->all(), 'subject'),
        ]);

        if ($request->ajax()) {
            return response('', JsonResponse::HTTP_RESET_CONTENT);
        }

        flash('Email updated')->success();

        return redirect()->route('emails.show', $email);
    }

    /**
     * @param Email $email
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh(Email $email)
    {
        $email->pickArticles();

        flash('Content refreshed')->success();

        return redirect()->back();
    }

    /**
     * @param Email $email
     * @return \App\Mail\LegacySubscriptionEmail|SubscriptionEmail
     */
    public function preview(Email $email)
    {
        return $email->getMailable();
    }

    /**
     * Generates an email preview based in non-persisted data
     *
     * @param Request        $request
     * @param EmailPresenter $presenter
     * @return SubscriptionEmail
     */
    public function previewUnpersisted(Request $request)
    {
        $data = $this->clearNullData($request->toArray());
        $presenter = new EmailPresenter();

        return new SubscriptionEmail($presenter->build($data));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getLegalUpdates(Request $request)
    {
        // return Article::mostRecent()
        //     ->whereEmailDateIsBetween(7, Carbon::parse($request->input('date')))
        //     ->ofType('legal update')
        //     ->get();

        $service = brandService('wtr');
        $search = $service->newSearch();

        $search
            ->setTagIds([ArticleType::TAG_ID_LEGAL_UPDATE])
            ->setPageSize(40)
            ->setSort(Search::SORT_TYPE_LATEST)
            ->withContent();

        $result = $service->run($search, 1)->hydrate();

        return $result;
    }

    /**
     * @param SendEmailRequest $request
     * @param Email            $email
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public function review(SendEmailRequest $request, Email $email)
    {
        $recipient_count = $email->sendToReviewers();

        if ($request->ajax()) {
            return [
                'status' => 'success',
                'msg' => 'Email sent to ' . $recipient_count . ' ' . Str::plural('reviewer', $recipient_count),
            ];
        }

        flash('Email sent to ' . $recipient_count . ' ' . Str::plural('reviewer', $recipient_count))->success();

        return redirect()->route('emails.show', $email);
    }

    /**
     * @param SendEmailRequest $request
     * @param Email            $email
     * @return array|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function send(SendEmailRequest $request, Email $email)
    {
        $campaignUrl = $email->dispatchNewsletterToService();

        try {
            $email->scheduleNewsletter();
        } catch (\Exception $exception) {
            app('sentry')->captureException($exception);
            flash('Unable to schedule the newsletter');
        }

        if ($request->ajax()) {
            return [
                'location' => $campaignUrl,
            ];
        }

        flash('Email sent to ' . $email->service_friendly_name)->success();

        return ($email->service_friendly_name == 'Communigator') ? redirect()->route('emails.show', $email) : redirect($campaignUrl);
    }

    /**
     * @param Email $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Email $email)
    {
        return view('emails.delete', compact('email'));
    }

    /**
     * @param DeleteEmailRequest $request
     * @param Email              $email
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(DeleteEmailRequest $request, Email $email)
    {
        if (! $email->is_sent && $email->delete()) {
            flash('Email deleted')->success();
        } else {
            flash('Email could not be deleted')->error();
        }

        return redirect()->route('emails.index');
    }

    /**
     * @param string         $brand
     * @param string         $type
     * @param EmailService   $service
     * @param EmailPresenter $presenter
     * @return array
     */
    public function defaultBanners(string $brand, string $type, EmailService $service, EmailPresenter $presenter)
    {
        $emailData = $service->getData($brand, $type);

        $presenter->setData([
            'brand' => $brand,
            'type' => $type,
        ]);

        return $presenter->buildBannersData();
    }

    protected function clearNullData($data)
    {
        $indexesToClearOfNull = [
            'industry_reports.article_ids',
            'international_reports.article_ids',
            'news_and_updates.article_ids',
            'thought_leadership.article_ids',
        ];

        foreach ($indexesToClearOfNull as $index) {
            if ($dataIndex = Arr::get($data, $index)) {
                Arr::set($data, $index, array_filter($dataIndex));
            }
        }

        return $data;
    }
}
