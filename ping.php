<?php

require_once 'include/bootstrap.php';
require_once 'include/queue.php';
global $conf;

$q = new Queue($conf['json_queue']);
$q->load();

/*
$q->links[] = [
  'url' => 'https://youtube.com/',
  'collection' => 'video_editor',
  'status' => 'downloading',
  'title' => 'Unknown',
  'timestamp' => time(),
];
*/

header('Content-type: application/json');
print $q->json();
exit;
