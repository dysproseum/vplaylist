<?php

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
