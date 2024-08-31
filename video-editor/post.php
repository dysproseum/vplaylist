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

error_log("New link queued for collection: " . $machine_name);

// Check for existing links.
if (file_exists($p)) {
  $links = json_decode(file_get_contents($p), true);
}
else {
  $links = [];
}

$links[] = [
  'url' => $url,
  'collection' => $machine_name,
//  'status' => '',
//  'title' => '',
];

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

// @todo redirect to download.php for status.

?>

<link rel="stylesheet" href="../include/style.css">

<div class="subnav">
</div>
<div class="listing-box">
  <div class="listing">
    <form action="post.php" class="video-editor">
      <h2>Check back soon!</h2>

      <h4><a href="index.php">Add Another Video</a></h4>

      <h4><a href="/vplaylist/index.php?collection=<?php print machine_name($conf['import_collection']); ?>">View Uploaded Videos</a></h4>
    </form>
  </div>
</div>
