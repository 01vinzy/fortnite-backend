<?php

namespace App\Api;

use GuzzleHttp\Client;

class Endpoints
{

  public static function oauth_token() {
    return "https://account-public-service-prod03.ol.epicgames.com/account/api/oauth/token";
  }

  public static function oauth_exchange() {
    return "https://account-public-service-prod03.ol.epicgames.com/account/api/oauth/exchange";
  }

  public static function lookup($username) {
      return "https://persona-public-service-prod06.ol.epicgames.com/persona/api/public/account/lookup?q=" . urlencode($username);
  }

  public static function lookupByIds($arr) {
    $queries = [];
    foreach($arr as $id) array_push($queries, "accountId=$id");
    return "https://account-public-service-prod03.ol.epicgames.com/account/api/public/account?" . join("&", $queries);
  }

  public static function statsBR($id, $window) {
    return "https://fortnite-public-service-prod11.ol.epicgames.com/fortnite/api/stats/accountId/$id/bulk/window/$window";
  }

  // public static function generalBearerRequest ($access_token, $endpoint) {
  //   $client = new Client(['http_errors' => false]);
  //   $res = $client->request("GET", $endpoint, [
  //     "headers" => [
  //       "Authorization" => "bearer $access_token"
  //     ]
  //   ])->getBody();
  //   return json_decode($res->getContents(), true);
  // }

}
