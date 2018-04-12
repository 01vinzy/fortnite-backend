<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
  protected $table = "timeline";

  public static function byid($id) {
    return static::where('uid', $id)->orderBy('created_at', 'desc');
  }

  public static function renameStats(&$mode) {
    $mode->minutesplayed = $mode->minutesplayed ?? 0;
    $mode->lastmodified = $mode->lastmodified ?? 0;
    $mode->score = $mode->score ?? 0;
    $mode->kills = $mode->kills ?? 0;
    $mode->wins = $mode->wins ?? 0;
    $mode->matches = $mode->matches ?? 0;

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

  public static function prettyStats($info) {

    $info = toObject($info);
    foreach($info as $key => $value) {
      if (!starts_with($key, "pc:") && !starts_with($key, "xb1:") && !starts_with($key, "ps4:")) continue;
      list($plat, $mode, $stat) = explode(":", $key);
      $info->stats = $info->stats ?? new \stdClass;
      $info->stats->$plat = $info->stats->$plat ?? new \stdClass;
      $info->stats->$plat->$mode = $info->stats->$plat->$mode ?? new \stdClass;
      $info->stats->$plat->$mode->$stat = $value;
      unset($info->$key);
    }
    foreach($info->stats as &$platform) {
      foreach($platform as &$mode) {
        static::renameStats($mode);
      }
    }
    unset($info->created_at);

    return $info;
  }
}
