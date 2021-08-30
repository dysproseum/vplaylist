<?php

define('CONFIG_PATH', './collections/');
define('THUMBS_PATH', './thumbnails/');

global $collections;
$collections = array();
foreach(glob(CONFIG_PATH.'*.json') as $filename){
	$collection = json_decode(file_get_contents($filename), true);
	if ($collection) {
		foreach ($collection as $name => $values) {
			$collections[$name] = $values;
			$machine_name = $name;
		}
	}
}
