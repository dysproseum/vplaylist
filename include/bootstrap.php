<?php

$script = dirname(__FILE__) . '/../config.php';
if (file_exists($script)) {
	require_once($script);
}

define('CONFIG_PATH', './collections/');
define('THUMBS_PATH', './thumbnails/');

global $collections;
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

function human_filesize($bytes, $dec = 2) {
    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
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
