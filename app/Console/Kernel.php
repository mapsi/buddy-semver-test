<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Running the scheduler
        // logger('Running scheduler');

        $schedule->command('global:runexports')->everyMinute()->name('Report generate')->withoutOverlapping()->onOneServer();

        $schedule->command('global:cacheviews hour')->everyMinute()->name('cache views on hour')->withoutOverlapping()->onOneServer();
        $schedule->command('global:cacheviews day')->hourly()->name('cache views for the hour')->withoutOverlapping()->onOneServer();
        $schedule->command('global:cacheviews month')->monthly()->name('cache views for the month')->withoutOverlapping()->onOneServer();

        $schedule->command('import:entities --all')->everyFiveMinutes()->name('import entities')->withoutOverlapping()->onOneServer();

        $schedule->command('subscriptions:expire')
            ->daily()
            ->name('Expire subscriptions')
            ->withoutOverlapping()->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
