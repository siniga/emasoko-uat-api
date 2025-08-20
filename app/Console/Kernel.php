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
        Commands\CreateCollectionBatch::class,
        Commands\PushCollectionBatch::class,
        Commands\CollectionStatusBatch::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // collection batches 
        $schedule->command('create:batches')->dailyAt('01:00');
        //$schedule->command('push:batches')->everyTwoHours();
        //$schedule->command('collection:status')->dailyAt('03:00');

        //summary reports
        $schedule->command('send:daily-report-summary')->dailyAt('23:50');
        //$schedule->command('send:monthly-report-summary')->lastDayOfMonth('23:30');
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
