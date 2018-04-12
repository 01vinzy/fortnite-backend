<?php

function req($object) {
  $method = $object["method"];
  $url = $object["url"];
  $client = new \GuzzleHttp\Client(["http_errors"=>false]);
  unset($object["method"]);
  unset($object["url"]);
  return toObject(json_decode($client->request($method, $url, $object)->getBody(), true));
}

function toObject($array) {
    $obj = new stdClass();
    foreach ($array as $key => $val) {
        $obj->$key = is_array($val) ? toObject($val) : $val;
    }
    return $obj;
}

function objRename(&$obj, $old, $new, $value=null) {
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

function duration($seconds) {
  if ($seconds == 0)
    return "none";
  if ($seconds < 60)
    return sprintf("%ds", $seconds%60);
  if ($seconds < 3600)
    return sprintf("%dm %ds", ($seconds/60)%60, $seconds%60);
  else
    return sprintf("%dh %dm %ds", floor($seconds/3600), ($seconds/60)%60, $seconds%60);
}

function ratio($num1, $num2) {
  if ($num2 === 0) return 0;
  return floor($num1 / $num2 * 100) / 100;
}

function timeago($timestamp) {
  if ($timestamp === 0) return "never";
  return \Carbon\Carbon::now()->timestamp($timestamp)->diffForHumans();
}
