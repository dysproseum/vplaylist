<?php

require_once '../include/bootstrap.php';

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

      <h4><a href="/vplaylist/index.php?collection=<?php print machine_name($conf['import_collection']); ?>">View Uploaded Videos</a></h4>
    </form>
  </div>
</div>
