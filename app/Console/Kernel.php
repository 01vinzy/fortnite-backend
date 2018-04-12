<?php

namespace App\Console;

use App\User;
use App\Log;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ServeApplicationCommand::class,
        Commands\KeyGenerateCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
          $users = User::get();
          // $user = $users[0];
          foreach($users as $user) {

            $username = rawurlencode($user->username);
            $url = env("APP_URL") . "/stats/$username";
            Log::make(5, "kernel", "Attempted Update: $url");


            req([
              "method" => "get",
              "url" => $url
            ]);
          }
        })->everyMinute();
    }
}
