<?php

namespace App\Http\Controllers;

use App\Api\Facades\Api;
use App\Timeline;
use App\User;
use App\Log;


class MainController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private function getMatch($info) {
      $old = Timeline::byid($info->id)->exists();
      User::firstOrCreate([
        "uid" => $info->id,
        "username" => $info->username
      ]);
      if ($old) {
        $old = Timeline::byid($info->id)->first();
        $platforms = config('platforms');
        $modes = config('modes');
        foreach($platforms as $p) {
          foreach($modes as $m => $mode) {
            if (!isset($info->raw->$p)) continue;
            if (!isset($info->raw->$p->$m)) continue;
            if ($info->raw->$p->$m->matchesplayed !== $old->{"$p:$m:matchesplayed"}) return "$p:$m";
          }
        }
        return false;
      }
      return "N/A";
    }

    private function addToTimeline($info)
    {
        $lastMatch = $this->getMatch($info);
        if (!!$lastMatch) {
          $t = new Timeline;
          $t->uid = $info->id;
          $t->username = $info->username;
          $t->match = $lastMatch;
          // dd($lastMatch);
          foreach($info->raw as $p => $platform) {
            foreach($platform as $m => $mode) {
              foreach($mode as $s => $stat){
                $t->{"$p:$m:$s"} = $stat;
              }
            }
          }
          $t->save();
          if ($lastMatch === "N/A") {
            Log::make(7, "MainController", "User Created: $info->username");
          } else {
            Log::make(7, "MainController", "User Updated: $info->username");
          }
        }
    }

    private function insertStats($username) {
      $username = urldecode($username);
      $user = Api::getUserByUsername($username);
      $info = Api::getUserInfo([
        "id" => $user->id
      ]);
      foreach ($info->raw as $key => $platform){
        $this->addToTimeline($info, $key, $platform);
      }
      $stats = Timeline::byid($info->id)->first()->toArray();
      $new = Timeline::prettyStats($stats);
      $info->stats = $new->stats;
      $info->last_match = $new->match;
      return $info;
    }

    public function AllStats ($username) {
      $info = $this->insertStats($username);
      return response()->json($info);
    }

    public function PlatformStats ($username, $platform) {
      $info = $this->insertStats($username);
      $info->stats = $info->stats->$platform;
      $info->selected_platform = $platform;
      return response()->json($info);
    }


    public function AllHistory($username) {
      $username = urldecode($username);
      $history = Timeline::where('username', $username)->orderBy('created_at', 'desc')->get()->toArray();
      $c = count($history);
      $results = [];
      // var_dump($history[1]);

      for ($i=0; $i < $c; $i++) {
        $match = $history[$i];
        $lastMatch = $history[$i + 1] ?? NULL;
        if (!$lastMatch) break;
        $match = toObject($match);
        $lastMatch = toObject($lastMatch);
        $diff = new \stdClass;
        foreach($match as $key => $value) {
          if (!starts_with($key, $match->match)) continue;
          list($plat, $mode, $stat) = explode(":", $key);
          $diff->$stat = $value - $lastMatch->$key;
          if ($stat === "lastmodified") {
            $diff->$stat = $value;
          }
          $diff->match = $match->match;
        }
        Timeline::renameStats($diff);
        objRename($diff, "last_played", "played");
        objRename($diff, "time_played", "match_length");
        unset($diff->matches);
        unset($diff->win_percent);
        unset($diff->kills_per_match);
        unset($diff->average_match);
        unset($diff->score_per_match);
        array_push($results, $diff);
      }
      return $results;
    }
}
