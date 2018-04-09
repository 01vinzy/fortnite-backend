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

  private function request($obj) {
    $method = $obj["method"];
    $url = $obj["url"];
    unset($obj["method"]);
    unset($obj["url"]);
    $res = $this->client->request($method, $url, $obj)->getBody();
    return json_decode($res->getContents(), true);
  }

  private function rename(&$obj, $old, $new, $value=null) {
    if (is_array($obj)) {
      if (isset($obj[$old])) {
        $obj[$new] = ($value !== null) ? $value : $obj[$old];
        unset($obj[$old]);
      }
    } else {
      if (isset($obj->$old)) {
        $obj->$new = ($value !== null) ? $value : $obj->$old;
        unset($obj->$old);
      }
    }

  }

  private function getAccess() {
    $res = $this->request([
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
    $res = $this->request([
      "method" => "get",
      "url" => Endpoints::oauth_exchange(),
      "headers" => [
        "Authorization" => "bearer {$data['access_token']}"
      ]
    ]);
    return $this->confirmBearer($res);
  }

  private function confirmBearer ($data) {
    $res = $this->request([
      "method" => "post",
      "url" => Endpoints::oauth_token(),
      "headers" => [
        "Authorization" => "basic $this->game_token"
      ],
      "form_params" => [
        "grant_type" => "exchange_code",
        "exchange_code" => $data['code'],
        "token_type" => "egl",
        "includePerms" => true
      ]
    ]);
    return $this->login($res);
  }

  private function login ($data) {
    $this->expires_at = $data["expires_at"];
    $this->access_token = $data["access_token"];
    $this->refresh_token = $data["refresh_token"];
    $this->user = $this->lookupByIds([$data["account_id"]])[0];
    return $this;
  }

  public function lookupByIds($ids) {
    return $this->request([
      "method" => "get",
      "url" => Endpoints::lookupByIds($ids),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
  }

  public function getUserByUsername($username) {
    return (object) $this->request([
      "method" => "get",
      "url" => Endpoints::lookup($username),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
  }

  public function getUserById($id) {
    return (object) $this->request([
      "method" => "get",
      "url" => Endpoints::lookupByIds([$id]),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ])[0];
  }

  private function getStats($id, $window) {
    $res = (object) $this->request([
      "method" => "get",
      "url" => Endpoints::statsBR($id, $window),
      "headers" => [
        "Authorization" => "bearer $this->access_token"
      ]
    ]);
    return empty($res) ? null : $res;
  }

  private function reduceUser($user) {
    $this->rename($user, 'displayName', 'username');

    $user->aliases = (object) [];
    foreach($user->externalAuths as $key => $value) {
      $user->aliases->$key = $value['externalDisplayName'];
    }
    unset($user->externalAuths);
    return $user;
  }

  private function reduceStats($stats) {
    $stats = array_reduce((array)$stats, function ($map, $obj) {
      $name = explode("_", $obj["name"]);
      if (!isset($map[$name[2]])) $map[$name[2]] = [];

      if ($name[4] === "p10") $name[4] = "duo";
      elseif ($name[4] === "p9") $name[4] = "squad";
      elseif ($name[4] === "p2") $name[4] = "solo";

      if (!isset($map[$name[2]][$name[4]])) $map[$name[2]][$name[4]] = [];
      $map[$name[2]][$name[4]][$name[1]] = $obj["value"];

      return $map;
    });
    return (object) $stats;
  }

  private function duration($seconds) {
    if ($seconds == 0)
      return "none";
    if ($seconds < 60)
      return sprintf("%ds", $seconds%60);
    if ($seconds < 3600)
      return sprintf("%dm %ds", ($seconds/60)%60, $seconds%60);
    else
      return sprintf("%dh %dm %ds", floor($seconds/3600), ($seconds/60)%60, $seconds%60);
  }

  private function ratio($num1, $num2) {
    if ($num2 === 0) return 0;
    return floor($num1 / $num2 * 100) / 100;
  }

  private function timeago($timestamp) {
    if ($timestamp === 0) return "never";
    return Carbon::now()->timestamp($timestamp)->diffForHumans();
  }

  private function prettyStats($stats) {
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

        $this->rename($mode, 'placetop1', 'wins');
        $this->rename($mode, 'placetop3', 'top3');
        $this->rename($mode, 'placetop5', 'top5');
        $this->rename($mode, 'placetop6', 'top6');
        $this->rename($mode, 'placetop10', 'top10');
        $this->rename($mode, 'placetop25', 'top25');
        $this->rename($mode, 'matchesplayed', 'matches');
        $mode->kd = $this->ratio($mode->kills, ($mode->matches - $mode->wins));
        $mode->win_percent = $this->ratio($mode->wins * 100, $mode->matches);
        $mode->kills_per_minute = $this->ratio($mode->kills, $mode->minutesplayed);
        $mode->kills_per_match = $this->ratio($mode->kills, $mode->matches);
        $mode->average_match = $this->duration($this->ratio($mode->minutesplayed, $mode->matches) * 60);
        $mode->score_per_match = $this->ratio($mode->score, $mode->matches);
        $mode->score_per_minute = $this->ratio($mode->score, $mode->minutesplayed);
        $this->rename($mode, 'minutesplayed', 'time_played', $this->duration($mode->minutesplayed * 60));
        $this->rename($mode, 'lastmodified', 'last_played', $this->timeago($mode->lastmodified));
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
    $user->stats = $this->prettyStats($this->reduceStats($stats));
    $user->raw = $stats;
    $user->updated_at = Carbon::now()->timestamp;
    $user->last_updated = $this->timeago($user->updated_at);
    $user->selected_platform = null;
    return $this->reduceUser($user);
  }


}
