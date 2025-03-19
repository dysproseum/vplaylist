<?php

// We know this should be the parent directory.
$htmlpath = dirname(__FILE__) . "/../";
chdir($htmlpath);
require_once 'include/bootstrap.php';
global $collections;

// @todo dependency check on admin user

if (!isset($_REQUEST['video1'])) {
  exit;
}

// Sanitize url.
$url = filter_var($_REQUEST['video1'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
if (!$url) {
  exit('Invalid URL');
}

// @todo check for needed file write locations

// Set file path.
$video_editor_dir = $conf['video_dir'] . "/video-editor";
$p = $video_editor_dir . "/links.json";

$machine_name = $_REQUEST['select_collection_name'];
if (!isset($collections[$machine_name])) {
  exit('Invalid collection ' . $machine_name);
}

error_log("New link queueing for collection: " . $machine_name);

// Check for playlist.
if (strstr($url, 'playlist')) {

  $cmd = 'yt-dlp --get-id --flat-playlist "' . $url . '"';
error_log($cmd);
  $playlist = shell_exec($cmd);
error_log($playlist);
  foreach (explode("\n", $playlist) as $video_id) {
    if (!$video_id) {
      continue;
    }
    error_log("Found $video_id");
    // Make a request to this page?
    $self = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $self);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
      'video1' => "https://youtu.be/$video_id",
      'select_collection_name' => $machine_name,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);
  }

  // Redirect to download.php for status.
  header('Location: download.php');
  exit;
}

// Check for existing links.
if (file_exists($p)) {
  $links = json_decode(file_get_contents($p), true);
}
else {
  $links = [];
}

$item = [
  'url' => $url,
  'collection' => $machine_name,
  'status' => 'new',
  'timestamp' => time(),
];

$links[] = $item;

$fp = fopen($p, 'wb');
if ($fp) {
  fputs($fp, json_encode($links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  fputs($fp, PHP_EOL);
  fclose($fp);
  chmod($p, 0777);
}
else {
  exit('Error writing links');
}

// Redirect to download.php for status.
header('Location: download.php');
exit;
