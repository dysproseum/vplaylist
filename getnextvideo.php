<?php

if (!isset($_REQUEST['collection'])) {
  header('HTTP/1.1 404 Not found');
  exit('No collection specified');
}
$machine_name = $_REQUEST['collection'];

require_once 'include/bootstrap.php';
global $collections;
$queue_size = sizeof($collections[$machine_name]['items']);

if (!isset($_REQUEST['index'])) {
  // Randomize from collection to start shuffle.
  $index = rand(0, $queue_size);
  $url = "index.php?collection=$machine_name&index=$index&autoplay=1&shuffle=1";
  header("Location: $url");
  exit;
}
$index = $_REQUEST['index'];

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
  header('HTTP/1.1 404 Not found');
  exit('Invalid index ' . $index . '.');
}

$item = $items[$index];
$item['index'] = $index;
$item['collection'] = $machine_name;
$item['filename'] = basename($item['filename'], '.mp4');
$item['queue_size'] = $queue_size;

print json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
