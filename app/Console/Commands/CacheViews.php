<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Brand;
use App\Models\View;
use App\Models\ViewCount;
use Illuminate\Console\Command;

class CacheViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:cacheviews {mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache views based on rules, ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mode = $this->argument('mode');
        $method = 'mode' . ucfirst($mode);
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->error('Unknown mode ' . $mode);
        }
    }

    /**
     * Computes the current days views other than this hours
     */
    protected function modeDay()
    {
        $end = Carbon::now()->startOfHour()->subSecond();
        $start = $end->copy()->startOfDay();

        $this->primeCache($start, $end);

        \DB::table('view_counts')
            ->orWhere(function ($query) use ($start, $end) {
                $query->where('start', '>=', $start)
                    ->where('end', '<', $end);
            })
            ->orWhere(function ($query) use ($start, $end) {
                $query->where('start', '>', $start)
                    ->where('end', '<=', $end);
            })->delete();
    }

    /**
     * Computes the current days views other than this hours
     */
    protected function modeMonth()
    {
        $end = Carbon::now()->startOfMonth()->subSecond();
        $start = $end->copy()->startOfMonth();

        $this->primeCache($start, $end);

        \DB::table('view_counts')
            ->orWhere(function ($query) use ($start, $end) {
                $query->where('start', '>=', $start)
                    ->where('end', '<', $end);
            })
            ->orWhere(function ($query) use ($start, $end) {
                $query->where('start', '>', $start)
                    ->where('end', '<=', $end);
            })->delete();
    }

    /**
     * Computes the current hours views
     */
    protected function modeHour()
    {
        $start = Carbon::now()->startOfHour();
        $end = $start->copy()->endOfHour();
        $this->primeCache($start, $end);
    }

    /**
     * This mode is for the first build of the table or later if we change the rules
     */
    protected function modeInit()
    {
        $first = View::OrderBy('created_at')->first()->created_at->startOfMonth();
        $end = $first->copy()->endOfMonth();

        //sort out each month from the begining up to this month
        while ($first->lt(Carbon::now()->startOfMonth())) {
            $end = $first->copy()->endOfMonth();
            $this->primeCache($first, $end);
            $first->addMonth();
        }

        //sort out each day in this month up to today
        while ($first->lt(Carbon::now()->startOfDay())) {
            $end = $first->copy()->endOfDay();
            $this->primeCache($first, $end);
            $first->addDay();
        }

        //sort out today's hours
        while ($first->lt(Carbon::now()->addHour())) {
            $end = $first->copy()->endOfHour();
            $this->primeCache($first, $end);
            $first->addHour();
        }
    }

    /**
     * use the start and end dates to populate the data
     * this might not be the best way to do day and month they might be better being insert sums of
     * the other data currently this is one step at a time / KISS
     *
     * @param type $start
     * @param type $end
     */
    protected function primeCache($start, $end)
    {
        $query = View::select([\DB::raw('count(*) as count'),
                               'routable_id',
                               'routable_type',
                               'brand_machine_name'])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->whereNotNull('browser')
            ->where('is_robot', '=', 0)
            ->where('ip', '!=', '178.62.65.161') // TODO is this IP relevant?
            ->groupBy('routable_id')
            ->groupBy('routable_type')
            ->groupBy('brand_machine_name');

        $results = $query->get();

        foreach ($results as $result) {
            $brand = Brand::findByMachineName($result->brand_machine_name ?: '');

            ViewCount::updateOrCreate([
                'countable_id' => $result->routable_id,
                'countable_type' => $result->routable_type,
                'brand_id' => $brand !== null ? $brand->id : 0,
                'start' => $start,
                'end' => $end,
            ], [
                'count' => $result->count,
            ]);
        }
    }
}
