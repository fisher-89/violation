<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\billCommand::class,
        Commands\PushCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function(){
            DB::table('bill_staff')->insert(['bill_id'=>4,'staff_sn'=>110103]);
        })->everyMinute();
        $schedule->command('punish:billCommand')->monthlyOn(1, '2:30');//月推送
//        $schedule->command('punish:pushCommand')->dailyAt('21:00');//日推送
        $schedule->command('punish:pushCommand')->everyMinute();
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

    /**
     * Get the Artisan application instance.
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        $artisan = parent::getArtisan();
        $artisan->setName('punish ( For Larvel )');

        return $artisan;
    }
}
