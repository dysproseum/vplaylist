<?php

// Load paths from config.
$config_path = dirname(__FILE__) . "/../config.php";
if (file_exists($config_path)) {
  require_once($config_path);
}
else {
  print "Could not load host paths from config_path.\n";
  print "Expected: $config_path\n";
  exit;
}

if (!isset($_REQUEST['video1'])) {
  exit;
}

$url = $_REQUEST['video1'];

// Sanitize url.
$url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
if (!$url) {
  exit('Invalid URL');
}

// @todo dependency check on admin page
// check for needed file write locations

// Set file path.
$video_editor_dir = $conf['video_dir'] . "/video-editor";
$p = $video_editor_dir . "/links.txt";

$handle = fopen($p, 'a');
if ($handle) {
  fputs($handle, $url . PHP_EOL);
  fclose($handle);
}
else {
  exit('Error writing links');
}
?>

<link rel="stylesheet" href="../include/style.css">

<div class="subnav">
</div>
<div class="listing-box">
  <div class="listing">
    <form action="post.php" class="video-editor">
      <h2>Check back soon!</h2>

      <h4><a href="index.php">Add Another Video</a></h4>

      <h4><a href="/vplaylist/index.php?collection=imported_videos">View Uploaded Videos</a></h4>
    </form>
  </div>
</div>
