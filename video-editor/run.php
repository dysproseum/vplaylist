<?php

// We know this should be the parent directory.
$htmlpath = dirname(__FILE__) . "/../";
chdir($htmlpath);
require_once 'include/bootstrap.php';
global $collections;

global $conf;
define('EXTERNAL_MEDIA', $conf['external_media']);
define('MEDIA_HOSTNAME', $conf['media_hostname']);
define('STORE_HOSTNAME', $conf['store_hostname']);
define('STORE_TARGET', $conf['store_target']);

// Define directories.
$video_editor_dir = $conf['video_dir'] . "/video-editor";
$p = $video_editor_dir . "/links.json";
// Rsync optional, ex. files stored on a NAS.
$rsync_target = STORE_HOSTNAME . ':' . STORE_TARGET;

// 1. Check pending requests.
if (!file_exists($p)) {
  exit;
}

// 2. Check for job in progress.
$queue = [];
$q = new Queue($p);
$q->pruneCompleted();
$queue = $q->queueLink();
$links = $q->getActiveLinks();

// If active, indicate progress in log file.
if (!empty($links)) {
  print ".";
  exit;
}

// Nothing to do.
if (empty($queue)) {
  if (DEBUG == 2) print "No unqueued links\n";
  exit;
}

dlog("Collections loaded: " . sizeof($collections));

// 3. Download queued links.
foreach ($queue as $link) {
  if (!isset($link['id'])) {
    continue;
  }
  $id = $link['id'];
  print "\n  [Slot $id] " . $link['url'];

  // Get duration.
  $cmd = "yt-dlp --get-duration " . $link['url'];
  $min_sec = exec($cmd);
  $q->setDisplayDuration($min_sec, $id);

  $segs = explode(":", $min_sec);
  $duration = 0;
  switch(sizeof($segs)) {
    case 2:
      $duration += $segs[0] * 60 + $segs[1];
      break;
    case 3:
      $duration += $segs[0] * 60 * 60 + $segs[1] * 60 + $segs[2];
      break;
  }
  print "\n  Duration: $duration seconds";
  // Get title.
  $cmd = "yt-dlp --get-title " . $link['url'];
  $title = exec($cmd);
  print "\n  $title";

  // Clean up characters before conversion.
  $title = str_replace('/', '_', $title);
  $title = str_replace('⧸', '_', $title);
  $title = str_replace(':', '- ', $title);
  $title = str_replace('"', '', $title);
  $title = iconv('UTF-8', 'ASCII//TRANSLIT',  $title);
  $title = str_replace('?', '', $title);
  if (strlen($title) > 255) {
    $title = substr($title, 0, 255);
  }
  $q->setTitle($title, $id);

  $download_dir = $video_editor_dir . "/download";
  $before = glob($download_dir . "/*");
  chdir($download_dir);

  $q->setStatus('downloading', $id);
  $elapsed = time();
  $cmd = "yt-dlp -o \"$title.%(ext)s\" " . $link['url'];
  vcmd($cmd, "Downloading...");
  print " (" . (time() - $elapsed) . "s)";

  // Get filename.
  $after = glob($download_dir . "/*");
  $diff = array_diff($after, $before);
  if (!empty($diff)) {
    $filename = basename(array_shift($diff));
    $q->setTitle($filename, $id);
    print "\n  " . $filename;
  }
  else {
    $q->setTitle(array_shift($after), $id);
    dlog("No new downloads found");
    if (DEBUG == 2) print_r($after);

    // Can't continue to match with final id without filename.
    $q->setError("Downloaded file not found", $id);
    continue;
  }

  // Get video duration.
  $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"$filename\"";
  $duration = exec($cmd);
  print "\n  Duration: $duration seconds";
  $q->setDuration($duration, $id);

  // 4. Process videos locally.
  $q->setStatus('processing', $id);
  $mp4_dir = $video_editor_dir . "/mp4";
  $before = glob($mp4_dir . "/*");
  $elapsed = time();
  $cmd = "cd $video_editor_dir && ./collect_mp4.sh";
  vcmd($cmd, "Processing media...");
  print " (" . (time() - $elapsed) . "s)";

  // Get filename.
  $after = glob($mp4_dir . "/*");
  $diff = array_diff($after, $before);
  if (!empty($diff)) {
    $filename = basename(array_shift($diff));
    $q->setTitle($filename, $id);
    print "\n  " . $filename;
  }
  else {
    $q->setTitle(array_shift($after), $id);
    dlog("No new mp4 found");
    if (DEBUG == 2) print_r($after);
  }

  // Verify import collection exist.
  $machine_name = $link['collection'];
  $import_dir = $conf['video_dir'] . '/' . $machine_name;
  if (!is_dir($import_dir)) {
    chdir($htmlpath);
    $cmd = 'php update.php create "' . $machine_name . '"';
    vcmd($cmd);
  }

  // Move converted files to data directory.
  chdir($video_editor_dir);
  $cmd = "mv mp4/* $import_dir/";
  vcmd($cmd);

  // Move downloads into originals folder or they get regenerated.
  $cmd = "mv download/* originals/";
  vcmd($cmd);


  // 5. Refresh.
  $q->setStatus('refreshing', $id);
  chdir($htmlpath);

  $elapsed = time();
  $cmd = "php update.php diff " . $machine_name;
  vcmd($cmd, "Comparing files...");
  print " (" . (time() - $elapsed) . "s)";

  print "\n  Collection " . $machine_name . ": " . sizeof($collections[$machine_name]['items']);

  $elapsed = time();
  $cmd = "php update.php gen " . $machine_name . " --overwrite";
  vcmd($cmd, "Writing collection...");
  print " (" . (time() - $elapsed) . "s)";

  $collections = load_collections();
  print "\n  Collection " . $machine_name . ": " . sizeof($collections[$machine_name]['items']);

  $q->setStatus('completed', $id);

  // Using filename, get the id to build the link to video.
  foreach ($collections[$machine_name]['items'] as $index => $item) {
    if (isset($item['filename']) && basename($item['filename']) == $filename) {
      $q->setIndex($index, $id);
      $url = "/vplaylist/index.php?collection=$machine_name&index=$index";
      $q->setTarget($url, $id);
    }
  }
  if ($q->getIndex($id) === false) {
    print "\n  Warning: Index not matched.";
  }

  // @todo notifications
}

dlog("Job completed.\n");

if (EXTERNAL_MEDIA) {
  print "\nTransferring to media processor...";
  $cmd = "rsync -av --exclude=links.txt* " . $video_editor_dir . ' ' . MEDIA_HOSTNAME . ":" . $video_editor_dir;
  exec($cmd);
  print "done.";

  // 4. Convert.
  print "\n[media_processor] Encoding media format...";
  $cmd = 'ssh ' . MEDIA_HOSTNAME . ' "cd ' . $video_editor_dir . ' && ./collect_mp4"';
  exec($cmd);
  print "done.";

  // 5. Transfer to storage.
  print "\n[media processor] Transferring to storage...";
  $cmd = 'ssh ' . MEDIA_HOSTNAME  . ' "rsync -av --exclude=links.txt* ' . $video_editor_dir . ' ' . STORE_HOSTNAME . ':' . STORE_TARGET . '"';
  exec($cmd);
  print "done.";

  print "\nTransferring to storage...";
  $cmd = "rsync -av --exclude=links.txt* " . $video_editor_dir . ' ' . STORE_HOSTNAME . ':' . STORE_TARGET;
  system($cmd);
  print "done.";
}
