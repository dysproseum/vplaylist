<?php

require_once 'include/bootstrap.php';
require_once 'include/queue.php';
global $conf;

if (file_exists($conf['json_queue'])) {
  $data = file_get_contents($conf['json_queue']);
  if ($data && strlen($data) > 0) {
    print $data;
    exit;
  }
  else {
    print json_encode(["error" => "file is empty"]);
  }
}
else {
  print json_encode(["error" => "file not exists, check queue_path and cron"]);
}
exit;
