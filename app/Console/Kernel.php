<?php

namespace App\Console;

use App\Console\Commands\BatchFileStatus;
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
        // Commands\CreateCollectionBatch::class,
        // Commands\PushCollectionBatch::class
        //BatchFileStatus::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // lists of commands
        $schedule->command('send:sms')->everyMinute();
        //$schedule->command('create:batches')->dailyAt('04:00');
        //$schedule->command('push:batches')->dailyAt('05:00');
        //$schedule->command('collection:status')->dailyAt('08:00');
        // $schedule->command('send:failed-batches')->dailyAt('09:00');
        // $schedule->command('send:failed-batches')->dailyAt('12:00');
        // $schedule->command('collection:status')->dailyAt('16:00');
        // $schedule->command('collection:status')->dailyAt('22:00');
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
