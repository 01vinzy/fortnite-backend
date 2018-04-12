<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Log;
use App\User;



$router->get('/stats/{username}', 'MainController@AllStats');
$router->get('/stats/{platform}/{username}', 'MainController@PlatformStats');
$router->get('/history/{username}', 'MainController@AllHistory');

$router->get('/test', function () {
      $users = User::get();

      foreach($users as $user) {
        Log::make(1, "kernel", "Attempted Update: $user->username");
        // $username = rawurlencode($user->username);
        // $url = url() . "/stats/$username";
        
        // req([
        //   "method" => "get",
        //   "url" => $url
        // ]);
      }
});