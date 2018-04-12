<?php

namespace App\Api;

use App\Api\Endpoints;
use GuzzleHttp\Client;
use Carbon\Carbon;

/**
 *
 */
class Api
{

  public function __construct($opt)
  {
    $this->launcher_token = $opt["launcher_token"];
    $this->game_token     = $opt["game_token"];
    $this->password       = $opt["password"];
    $this->username       = $opt["email"];
    $this->client = new Client(['http_errors' => false]);
    $this->getAccess();
  }

  // private function toObject($array) {
  //   $obj = new \stdClass();
  //   foreach ($array as $key => $val) {
  //       $obj->$key = is_array($val) ? toObject($val) : $val;
  //   }
  //   return $obj;
  // }
  //
  // private function request($obj) {
  //   $method = $obj["method"];
  //   $url = $obj["url"];
  //   unset($obj["method"]);
  //   unset($obj["url"]);
  //   $res = $this->client->request($method, $url, $obj)->getBody();
  //   return toObject(json_decode($res->getContents(), true));
  // }

  // private function rename(&$obj, $old, $new, $value=null) {
  //   if (is_array($obj)) {
  //     if (isset($obj[$old])) {
  //       $obj[$new] = ($value !== null) ? $value : $obj[$old];
  //       unset($obj[$old]);
  //     }
  //   } else {
  //     if (isset($obj->$old)) {
  //       $obj->$new = ($value !== null) ? $value : $obj->$old;
  //       unset($obj->$old);
  //     }
  //   }
  // }

  private function getAccess() {
    $res = req([
      "method" => "post",
      "url" => Endpoints::oauth_token(),
      "headers" => [
        "Authorization" => "basic $this->launcher_token"
      ],
      "form_params" => [
        "grant_type" => "password",
        "username" => $this->username,
        "password" => $this->password,
        "includePerms" => true
      ]
    ]);
    return $this->getBearer($res);
  }

  private function getBearer ($data) {
    $res = req([
      "method" => "get",
      "url" => Endpoints::oauth_exchange(),
      "headers" => [
        "Authorization" => "bearer $data->access_token"
      ]
    ]);
    return $this->confirmBearer($res);
  }

  private function confirmBearer ($data) {
    $res = req([
      "method" => "post",
      "url" => Endpoints::oauth_token(),
      "headers" => [
        "Authorization" => "basic $this->game_token"
      ],
      "form_params" => [
        "grant_type" => "exchange_code",
        "exchange_code" => $data->code,
        "token_type" => "egl",
        "includePerms" => true
      ]
    ]);
    return $this->login($res);
  }

  private function login ($data) {
    $this->expires_at = $data->expires_at;
    $this->access_token = $data->access_token;
    $this->refresh_token = $data->refresh_token;
    $this->user = $this->lookupByIds([$data->account_id])[0];
    return $this;
  }

  public function lookupByIds($ids) {
    return (array) req([
      "method" => "get",
      "url" => Endpoints::lookupByIds($ids),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
  }

  public function getUserByUsername($username) {
    return req([
      "method" => "get",
      "url" => Endpoints::lookup($username),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
  }

  public function getUserById($id) {
    return ((array) req([
      "method" => "get",
      "url" => Endpoints::lookupByIds([$id]),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]))[0];
  }

  private function getStats($id, $window) {
    $res = req([
      "method" => "get",
      "url" => Endpoints::statsBR($id, $window),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
    return empty($res) ? null : $res;
  }

  private function reduceUser($user) {
    objRename($user, 'displayName', 'username');

    $user->aliases = new \stdClass;
    $user->platforms = [];
    foreach($user->externalAuths as $key => $value) {
      $user->aliases->$key = $value->externalDisplayName;
    }
    foreach($user->raw as $key => $value) {
      array_push($user->platforms, $key);
    }
    unset($user->externalAuths);
    return $user;
  }

  private function reduceStats($stats) {
    $stats = array_reduce((array)$stats, function ($map, $obj) {
      $name = explode("_", $obj->name);
      if (!isset($map[$name[2]])) $map[$name[2]] = [];

      if ($name[4] === "p10") $name[4] = "duo";
      elseif ($name[4] === "p9") $name[4] = "squad";
      elseif ($name[4] === "p2") $name[4] = "solo";

      if (!isset($map[$name[2]][$name[4]])) $map[$name[2]][$name[4]] = [];
      $map[$name[2]][$name[4]][$name[1]] = $obj->value;

      return $map;
    });
    return toObject($stats);
  }



  public function prettyStats($stats) {
    $res = clone $stats;
    foreach($res as &$platform) {
      foreach($platform as &$mode) {
        $mode = (object) $mode;
        if (!isset($mode->minutesplayed)) $mode->minutesplayed = 0;
        if (!isset($mode->lastmodified)) $mode->lastmodified = 0;
        if (!isset($mode->score)) $mode->score = 0;
        if (!isset($mode->kills)) $mode->kills = 0;
        if (!isset($mode->wins)) $mode->wins = 0;
        if (!isset($mode->matches)) $mode->matches = 0;

        objRename($mode, 'placetop1', 'wins');
        objRename($mode, 'placetop3', 'top3');
        objRename($mode, 'placetop5', 'top5');
        objRename($mode, 'placetop6', 'top6');
        objRename($mode, 'placetop10', 'top10');
        objRename($mode, 'placetop12', 'top12');
        objRename($mode, 'placetop25', 'top25');
        objRename($mode, 'matchesplayed', 'matches');
        $mode->kd = ratio($mode->kills, ($mode->matches - $mode->wins));
        $mode->win_percent = ratio($mode->wins * 100, $mode->matches);
        $mode->kills_per_minute = ratio($mode->kills, $mode->minutesplayed);
        $mode->kills_per_match = ratio($mode->kills, $mode->matches);
        $mode->average_match = duration(ratio($mode->minutesplayed, $mode->matches) * 60);
        $mode->score_per_match = ratio($mode->score, $mode->matches);
        $mode->score_per_minute = ratio($mode->score, $mode->minutesplayed);
        objRename($mode, 'minutesplayed', 'time_played', duration($mode->minutesplayed * 60));
        objRename($mode, 'lastmodified', 'last_played', timeago($mode->lastmodified));
      }
    }
    return $res;
  }

  public function getUserInfo($obj) {
    $user = [];
    if (isset($obj["id"])) {
      $user = $this->getUserById($obj["id"]);
    }
    elseif (isset($obj["username"])) {
      $user = $this->getUserByUsername($obj["username"]);
      if (!isset($user->id)) return null;
      $user = $this->getUserById($user->id);
    };
    $stats = $this->getStats($user->id, "alltime");
    $user->raw = $this->reduceStats($stats);
    $user->updated_at = Carbon::now()->timestamp;
    $user->last_updated = timeago($user->updated_at);
    $user->selected_platform = null;
    return $this->reduceUser($user);
  }


}
