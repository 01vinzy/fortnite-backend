<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public static function make ($level, $source, $message) {
      $l = new Log;
      $l->level = $level;
      $l->source = $source;
      $l->message = $message;
      $l->save();
    }
}
