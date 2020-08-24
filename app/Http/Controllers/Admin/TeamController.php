<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CreateTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Repositories\TeamRepository;
use App\Http\Controllers\AppBaseController;
use App\Models\Product;
use App\Models\SubscriptionLevel;
use Illuminate\Http\Request;
use Flash;
use Prettus\Repository\Criteria\RequestCriteria;
use Response;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class TeamController extends AppBaseController
{
    /** @var  TeamRepository */
    private $teamRepository;

    public function __construct(TeamRepository $teamRepo)
    {
        $this->teamRepository = $teamRepo;
    }

    /**
     * Display a listing of the Team.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->teamRepository->pushCriteria(new RequestCriteria($request));
        if ($request->has('user_id')) {
            $this->teamRepository->where('user_id', '=', $request->input('user_id'));
        }
        if ($request->has('group')) {
            if ($request->input('group') == 'all') {
            } elseif ($request->input('group') == 'groups') {
                $this->teamRepository->hasCount('members', '>=', 2);
            } else {
                $this->teamRepository->hasCount('members', '=', 1);
            }
        }
        $brand = $request->input('brand', 'all');

        switch ($brand) {
            case 'all':
                break;
            case 'grr':
            case 'gcr':
                $this->teamRepository->whereHas('subscriptions', function (Builder $query) use ($brand) {
                    $query->whereHasMorph('subscribable', SubscriptionLevel::class, function (Builder $query) use ($brand) {
                        $query->where('brand_id', $brand);
                    });
                });
                break;
            default:
                //dont do multiple of these if you want to search somthing in subscriotions or product devide into multiple nested layers
                $this->teamRepository->whereHas('subscriptions', function (Builder $query) use ($brand) {
                    $query->whereHasMorph('subscribable', Product::class, function (Builder $query) use ($brand) {
                        $query->whereHas('brands', function (Builder $query) use ($brand) {
                            $query->where('id', $brand);
                        });
                    });
                });
                break;
        }
        $this->teamRepository->with('members')->with('primary');
        if ($request->input('sort')) {
            $parts = explode('_', $request->input('sort'));
            $direction = array_pop($parts);
            $this->teamRepository->orderBy(implode('_', $parts), $direction);
        }
        $teams = $this->teamRepository->with('subscriptions')->paginate();

        return view('admin.teams.index')
            ->with('teams', $teams);
    }
    public function lookup(Request $request)
    {
        $teams = Team::orWhere('name', 'like', '%' . $request->input('q') . '%')
            ->orWhere('id', 'like', $request->input('q') . '%')
            ->orWhere('firm_ref', 'like', $request->input('q') . '%')

            ->limit(10)->get();
        return $teams->transform(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->id . ' - ' . $item->name
            ];
        });
    }
    /**
     * Show the form for creating a new Team.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.teams.create');
    }

    /**
     * Store a newly created Team in storage.
     *
     * @param CreateTeamRequest $request
     *
     * @return Response
     */
    public function store(CreateTeamRequest $request)
    {
        $input = $request->all();

        $team = $this->teamRepository->create($input);

        Flash::success('Team saved successfully.');

        return redirect(route('teams.index'));
    }

    /**
     * Display the specified Team.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }

        return view('teams.show')->with('team', $team)->with('ongoing', $team->subscriptions()->ongoing()->get());
    }

    /**
     * Show the form for editing the specified Team.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }

        return view('admin.teams.edit')->with('team', $team);
    }

    /**
     * Update the specified Team in storage.
     *
     * @param  int              $id
     * @param UpdateTeamRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTeamRequest $request)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }

        $team = $this->teamRepository->update($request->all(), $id);

        Flash::success('Team updated successfully.');

        return redirect(route('teams.index'));
    }

    /**
     * Remove the specified Team from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }
        if ($team->subscriptions->count()) {
            Flash::error('Team has subscriptions remaining delete them first');

            return redirect(route('subscriptions.index'));
        }
        $this->teamRepository->delete($id);

        Flash::success('Team deleted successfully.');

        return redirect(route('teams.index'));
    }



    public function patchManageTeam($id, Request $request)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }
        $field = 'email';
        if (is_numeric($request->input('email'))) {
            $field = 'id';
        }
        $user = User::where($field, '=', $request->input('email'))->first();
        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('teams.show', [$team->id]));
        }
        $team->members()->syncWithoutDetaching([$user->id]);
        return redirect(route('teams.show', ['team' => $team]));
    }

    public function deleteManageTeam($id, $userid)
    {
        $team = $this->teamRepository->findWithoutFail($id);

        if (empty($team)) {
            Flash::error('Team not found');

            return redirect(route('teams.index'));
        }
        try {
            $team->members()->detach([$userid]);
        } catch (\Exception $e) {
        }
        return redirect(route('teams.show', ['team' => $team]));
    }
}
