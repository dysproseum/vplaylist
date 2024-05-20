<?php

if (!isset($_REQUEST['index']) || !isset($_REQUEST['collection'])) {
	exit('No index or collection specified');
}

require_once 'include/bootstrap.php';

$index = $_REQUEST['index'];
$machine_name = $_REQUEST['collection'];
$queue_size = sizeof($collections[$machine_name]['items']);

// Randomize from session queue.
if (isset($_REQUEST['shuffle'])) {
  session_start();

  if (!isset($_SESSION[$machine_name])) {
    fill_session_queue($machine_name);
  }
  unset($_SESSION[$machine_name]["p$index"]);
  $queue_size = sizeof($_SESSION[$machine_name]);

  // Played all, fill if repeat checked.
  if (empty($_SESSION[$machine_name])) {
    if (isset($_REQUEST['repeat'])) {
      fill_session_queue($machine_name);
    }
    else {
      unset($_SESSION[$machine_name]);
      $index = -1;
    }
  }
  else {
    $p_index = array_rand($_SESSION[$machine_name]);
    $index = $_SESSION[$machine_name][$p_index]['index'];
  }
}
else {
  // Increment normally.
  $index++;

  // Played all, fill if repeat checked.
  if ($index >= sizeof($collections[$machine_name]['items'])) {
    if (isset($_REQUEST['repeat'])) {
      $index = 0;
    }
  }
}

$items = $collections[$machine_name]['items'];
if (!isset($items[$index])) {
  exit('Invalid index ' . $index . '.');
}

$item = $items[$index];
$item['index'] = $index;
$item['base64'] = base64_encode(addslashes($item['filename']));
$item['filename'] = basename($item['filename'], '.mp4');
$item['queue_size'] = $queue_size;

print json_encode($item, JSON_PRETTY_PRINT);
