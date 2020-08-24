<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Repositories\SubscriptionRepository;
use App\Http\Controllers\AppBaseController;
use App\Models\SubscriptionLevel;
use Illuminate\Http\Request;
use Flash;
use Prettus\Repository\Criteria\RequestCriteria;
use Response;

class SubscriptionController extends AppBaseController
{
    /** @var  SubscriptionRepository */
    private $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepo)
    {
        $this->subscriptionRepository = $subscriptionRepo;
    }

    /**
     * Display a listing of the Subscription.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->subscriptionRepository->pushCriteria(new RequestCriteria($request));
        if ($request->has('team_id')) {
            $this->subscriptionRepository->where('team_id', '=', $request->input('team_id'));
        }
        if ($request->input('sort')) {
            $parts = explode('_', $request->input('sort'));
            $direction = array_pop($parts);
            if (! empty($parts)) {
                if ($parts[0] == 'team') {
                    $this->subscriptionRepository->join('teams', 'teams.id', 'team_id')->orderBy('teams.name', $direction);
                } elseif ($parts[0] == 'product') {
                    $this->subscriptionRepository->join('products', 'products.id', 'product_id')->orderBy('products.name', $direction);
                } else {
                    $this->subscriptionRepository->orderBy(implode('_', $parts), $direction);
                }
            }
        }
        $subscriptions = $this->subscriptionRepository->with('team')->paginate(null, ['subscriptions.*']);

        return view('subscriptions.index')
            ->with('subscriptions', $subscriptions);
    }

    /**
     * Show the form for creating a new Subscription.
     *
     * @return Response
     */
    public function create()
    {
        return view('subscriptions.create');
    }

    /**
     * Store a newly created Subscription in storage.
     *
     * @param CreateSubscriptionRequest $request
     *
     * @return Response
     */
    public function store(CreateSubscriptionRequest $request)
    {
        $input = $request->all();

        $subscription = $this->subscriptionRepository->create($input);

        if ($request->get('subscribable_type') === SubscriptionLevel::class) {
            $role = SubscriptionLevel::findOrFail($request->get('subscribable_id'));
            $subscription->assignRole($role);
        }

        Flash::success('Subscription saved successfully.');

        return redirect(route('subscriptions.index', ['team_id' => $subscription->team_id]));
    }

    /**
     * Display the specified Subscription.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $subscription = $this->subscriptionRepository->findWithoutFail($id);

        if (empty($subscription)) {
            Flash::error('Subscription not found');

            return redirect(route('subscriptions.index'));
        }

        return view('subscriptions.show')->with('subscription', $subscription);
    }

    /**
     * Show the form for editing the specified Subscription.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $subscription = $this->subscriptionRepository->findWithoutFail($id);

        if (empty($subscription)) {
            Flash::error('Subscription not found');

            return redirect(route('subscriptions.index'));
        }

        return view('subscriptions.edit')->with('subscription', $subscription);
    }

    /**
     * Update the specified Subscription in storage.
     *
     * @param  int              $id
     * @param UpdateSubscriptionRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateSubscriptionRequest $request)
    {
        $subscription = $this->subscriptionRepository->findWithoutFail($id);

        if (empty($subscription)) {
            Flash::error('Subscription not found');

            return redirect(route('subscriptions.index'));
        }

        $subscription = $this->subscriptionRepository->update($request->all(), $id);

        if ($request->get('subscribable_type') === SubscriptionLevel::class) {
            $role = SubscriptionLevel::findOrFail($request->get('subscribable_id'));
            $subscription->assignRole($role);
        }

        Flash::success('Subscription updated successfully.');

        return redirect(route('subscriptions.index', ['team_id' => $subscription->team_id]));
    }

    /**
     * Remove the specified Subscription from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $subscription = $this->subscriptionRepository->findWithoutFail($id);

        if (empty($subscription)) {
            Flash::error('Subscription not found');

            return redirect(route('subscriptions.index'));
        }

        $this->subscriptionRepository->delete($id);

        Flash::success('Subscription deleted successfully.');

        return redirect(route('subscriptions.index', ['team_id' => $subscription->team_id]));
    }
}
