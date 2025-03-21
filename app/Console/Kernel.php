<?php

// app/Console/Kernel.php
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
        Commands\SendReviewEmails::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Fetch orders and send emails daily
        $schedule->command('amazon:send-emails')
            ->dailyAt('01:00')
            ->appendOutputTo(storage_path('logs/amazon-emails.log'));
            
        // You could also run a more frequent check for pending emails
        $schedule->command('amazon:send-emails')
            ->hourly()
            ->appendOutputTo(storage_path('logs/amazon-emails-hourly.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}