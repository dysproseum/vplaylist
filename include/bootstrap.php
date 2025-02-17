<?php

require_once(dirname(__FILE__) . '/../config.php');
require_once('queue.php');

define('CONFIG_PATH', dirname(__FILE__) . '/../collections/');
define('THUMBS_PATH', './thumbnails/');

if (isset($conf['debug'])) {
  define('DEBUG', $conf['debug']);
}
else {
  define('DEBUG', false);
}

function load_collections() {
  $collections = array();
  foreach(glob(CONFIG_PATH.'*.json') as $filename) {
    $collection = json_decode(file_get_contents($filename), true);
    if ($collection) {
      foreach ($collection as $name => $values) {
        $collections[$name] = $values;
        $machine_name = $name;
      }
    }
  }
  return $collections;
}
$collections = load_collections();

function vlog($message, $override_action = '') {

  global $action;
  if (!isset($action)) {
    $action = $override_action;
  }
  if ($action != 'gen') {
    print $message;
  }
}

function dlog($message, $override_newline = FALSE) {
  $timestamp = date('Y-m-d h:ia');
  if ($override_newline) {
    $output = $message . " ";
  }
  else {
    print PHP_EOL . "$timestamp  $message";
  }
}

// Run command and log errors.
function vcmd($cmd, $message='') {
  if ($message != '') {
    print "\n$message";
  }

  $output = [];
  $result = exec("$cmd 2>&1", $output, $result_code);
  if (DEBUG || $result_code != 0) {
    foreach ($output as $line) {
      print "  $line\n";
    }
  }

  return $result_code;
}

function machine_name($collection_name) {
  $machine_name = strtolower($collection_name);
  $machine_name = preg_replace('/[^\w\s]+/', '', $machine_name);
  $machine_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $machine_name);
  return $machine_name;
}

function create_dir($new_dir) {
  if (!is_dir($new_dir)) {
    if (mkdir($new_dir)) {
      dlog("Directory $new_dir created.");
    }
    else {
      dlog("Directory $new_dir failed to create.");
    }
  }
  else {
    dlog("Directory $new_dir already exists.");
  }
}

function fill_session_queue($machine_name) {
  global $collections;
  foreach ($collections[$machine_name]['items'] as $i => $item) {
    $_SESSION[$machine_name]["p$i"] = $item;
    $_SESSION[$machine_name]["p$i"]['index'] = $i;
  }
}

function human_filesize($bytes, $dec = 2) {
    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function date_compare($a1, $a2) {
  return $a1['timestamp'] - $a2['timestamp'];
}

function date_compare_desc($a1, $a2) {
  return date_compare($a2, $a1);
}

function name_compare($a1, $a2) {
  return strcasecmp($a1['title'], $a2['title']);
}

function name_compare_desc($a1, $a2) {
  return name_compare($a2, $a1);
}

function get_video_duration($filename) {
  $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"$filename\"";
  return exec($cmd);
}

function get_video_framerate($filename) {
  $cmd = "ffprobe -v error -select_streams v -of default=noprint_wrappers=1:nokey=1 -show_entries stream=r_frame_rate \"$filename\"";
  return exec($cmd);
}

function seconds_to_clock_time($seconds) {
  $secs = $seconds % 60;
  $hrs = $seconds / 60;
  $mins = $hrs % 60;
  $hrs = $hrs / 60;

  return sprintf("%02d:%02d:%02d", $hrs, $mins, $secs);
}

function clock_time_to_seconds($min_sec) {
  // Get duration.
  // $min_sec = exec($cmd);
  // $q->setDisplayDuration($min_sec, $id);

  $segs = explode(":", $min_sec);
  $duration = 0;
  switch(sizeof($segs)) {
    case 2:
      $duration += $segs[0] * 60 + $segs[1];
      break;
    case 3:
      $duration += $segs[0] * 60 * 60 + $segs[1] * 60 + $segs[2];
      break;
  }
  // print "\n  Duration: $duration seconds";
  return $duration;
}

function is_mobile() {
	return preg_match("/(android|webos|avantgo|iphone|ipad|ipod|blackberry|iemobile|bolt|boost|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

function analytics() {
	$script = dirname(__FILE__) . '/analytics.php';
	if (file_exists($script)) {
		include($script);
	}
	else {
		return false;
	}
}
