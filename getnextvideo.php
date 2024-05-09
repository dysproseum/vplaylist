<?php

if (!isset($_REQUEST['index']) || !isset($_REQUEST['collection'])) {
	exit('No index or collection specified');
}

require_once 'include/bootstrap.php';

$index = $_REQUEST['index'];
$machine_name = $_REQUEST['collection'];

$items = $collections[$machine_name]['items'];
if (!isset($items[$index])) {
  exit('Invalid index');
}

$item = $items[$index];
$item['base64'] = base64_encode(addslashes($item['filename']));
$item['filename'] = basename($item['filename'], '.mp4');

print json_encode($item);
