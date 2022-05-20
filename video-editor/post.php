<?php

$urls = [];

if (!isset($_REQUEST['video1'])) {
  exit;
}

$urls[] = $_REQUEST['video1'];

// @todo dependency check on admin page
// check for needed file write locations

// @todo pull paths from config file.
$p = '/uploads/video-editor/links.txt';
$handle = fopen($p, 'a');
if ($handle) {

  foreach ($urls as $url) {
    fputs($handle, $url . PHP_EOL);
  }
  fclose($handle);
  
}
else {
  // error opening the file.
}

?>

<p>Check back soon!<p>

<p>
  <a href="index.php">Another Video</a>
</p>

<p>
  <a href="/vplaylist/index.php?collection=video_editor">Watch Videos</a>
</p>
