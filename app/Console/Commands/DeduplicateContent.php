<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeduplicateContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:deduplicate {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deduplicate content; currently supports event.speaker only.';

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
        \Log::info('DeduplicateContent: Execution commenced');

        /**
         * @todo: IF WE ARE GOING TO INCLUDE MORE CHECKS HERE, CONSIDER THAT WE ARE USING THIS WHILE
         * SYNCING EVENT SPEAKERS
         **/
        $duplicates = $this->getDuplicateEventSpeakers();

        if (count($duplicates) > 0) {
            \Log::info('DeduplicateContent: Found duplicates', $duplicates->toArray());

            if (! $this->option('dry-run')) {
                $this->deleteDuplicateEventSpeakers($duplicates);

                \Log::info('DeduplicateContent: Deleted');

                $this->rePopulateEventSpeakers($duplicates);

                \Log::info('DeduplicateContent: Inserted');
            }
        }

        \Log::info('DeduplicateContent: Execution completed');
    }

    /**
     * @return Collection
     */
    private function getDuplicateEventSpeakers(): Collection
    {
        $duplicates = DB::table('event_speaker')
            ->select(
                '*',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(['event_id', 'speaker_id'])
            ->havingRaw('COUNT(*) > ?', [1])
            ->get();

        return $duplicates;
    }

    /**
     * @param Collection $duplicates
     * @return bool
     */
    private function deleteDuplicateEventSpeakers(Collection $duplicates): bool
    {
        foreach ($duplicates as $duplicate) {
            DB::table('event_speaker')
                ->where([
                    [
                        'event_id',
                        '=',
                        $duplicate->event_id,
                    ],
                    [
                        'speaker_id',
                        '=',
                        $duplicate->speaker_id,
                    ],
                ])
                ->delete();
        }

        return true;
    }

    /**
     * @param Collection $duplicates
     * @return bool
     */
    private function rePopulateEventSpeakers(Collection $duplicates): bool
    {
        $insertArr = [];

        foreach ($duplicates as $duplicate) {
            $insertArr = [
                'event_id' => $duplicate->event_id,
                'speaker_id' => $duplicate->speaker_id,
            ];
        }

        DB::table('event_speaker')->insert($insertArr);

        return true;
    }
}
