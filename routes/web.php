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

use App\Api\Facades\Api;


$router->get('/stats/{username}', function ($username) use ($router) {
    $user = Api::getUserByUsername($username);
    return response()->json(Api::getUserInfo([
      "id" => $user->id
    ]));
});
