<?php

$urls = [];

if (!isset($_REQUEST['video1'])) {
  exit;
}

$urls[] = $_REQUEST['video1'];

// @todo dependency check on admin page
// check for needed file write locations

// @todo pull paths from config file.
$p = '/overflow/links.txt';
$handle = fopen($p, 'a');
if ($handle) {

  foreach ($urls as $url) {
    fputs($handle, $url . PHP_EOL);
  }
  fclose($handle);
  
}
else {
  // error opening the file.
  die("Error opening the file.");
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

      <h4><a href="/vplaylist/index.php?collection=video_editor">View Uploaded Videos</a></h4>
    </form>
  </div>
</div>
