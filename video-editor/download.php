<?php

// We know this should be the parent directory.
$htmlpath = dirname(__FILE__) . "/../";
chdir($htmlpath);
require_once 'include/bootstrap.php';
global $conf;

// Set file path.
$video_editor_dir = $conf['video_dir'] . "/video-editor";
$p = $video_editor_dir . "/links.json";

$q = new Queue($p);
$links = $q->load();

// Example data.
$links[] = [
  'url' => 'https://dysproseum.com/vplaylist',
  'collection' => 'misc',
  'status' => 'queued',
  'title' => 'Loading...',
  'timestamp' => time(),
];

$cnt = sizeof($links);

include(dirname(__FILE__) . "/../include/header.php");

?>

<link rel="stylesheet" href="../include/style.css">
<script type="text/javascript" src="../include/util.js"></script>
<script type="text/javascript" src="../include/ping.js"></script>

<div class="subnav">
  <h2>Import Status</h2>
  <h4><a href="index.php">Add Another Video</a></h4>
</div>
<div class="listing-box">
  <div class="listing">
    <form action="post.php" class="video-editor" id="imports">
    Loading...
    <?php if ($cnt == 0): ?>No active items<?php endif; ?>
    <?php foreach ($links as $index => $link): ?>
      <div class="status" id="index" hidden>
        <span class="info">
          <span class="title">
            <?php print $link['title'] ? $link['title'] : $link['url'];  ?>
          </span>
          <span class="collection">
            <?php print $link['collection']; ?>
          </span>
          <span class="status">
            <?php print $link['status']; ?>
          </span>
          <span class="timestamp">
            <?php print $link['timestamp']; ?>
          </span>
        </span>
        <!-- assign timestamps to each one -->
        <!-- handle timer and metadata at the queue level? -->
        <div class="queued">Queued (<span class="value">0</span>s)</div>
        <div class="downloading">Downloading</div>
        <div class="processing">Processing</div>
        <div class="refreshing">Refreshing</div>
      </div>
    <?php endforeach; ?>
    </form>

  </div>
</div>

<?php include(dirname(__FILE__) . "/../include/footer.php"); ?>