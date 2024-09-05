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

/***************************************************/

// 1. Check pending requests.
if (!file_exists($p)) {
  exit;
}

// 2. Check for job in progress.
$queue = [];
$q = new Queue($p);
$q->load();
$queue = $q->queueLinks();
$links = $q->getNonCompletedLinks();
$cnt = sizeof($queue);

// @todo keep completed items in JSON file for 24 hours.
// delete completed items with timestamp > 24 hours ago.
// $q->pruneCompleted();

// Indicate progress in log file.
if (empty($queue) && !(empty($links))) {
  print ".";
}

// Nothing to do.
if (empty($queue)) {
  if (DEBUG == 2) print "No unqueued links\n";
  exit;
}

if ($cnt == 1) {
  $plural_maybe = "There is 1 request";
}
else {
  $plural_maybe = "There are $cnt requests";
}
dlog("$plural_maybe in the queue.");

// 3. Download queued links.
foreach ($queue as $index => $link) {

  $q->setStatus('downloading', $index);

  $numeral = $index + 1;
  print "\n  [$numeral/$cnt] " . $link['url'];

  $download_dir = $video_editor_dir . "/download";
  $before = glob($download_dir . "/*");
  chdir($download_dir);

  $elapsed = time();
  $cmd = "yt-dlp " . $link['url'];
  vcmd($cmd, "Downloading...");
  print " (" . (time() - $elapsed) . "s)";

  // Get filename.
  $after = glob($download_dir . "/*");
  $diff = array_diff($after, $before);
  if (!empty($diff)) {
    $filename = basename(array_shift($diff));
    $q->setTitle($filename, $index);
    print "\n  " . $filename;
  }
  else {
    $q->setTitle(array_shift($after), $index);
    dlog("No new downloads found");
    if (DEBUG == 2) print_r($after);
  }

  // @todo clean up characters before conversion.
  $iconv = iconv('UTF-8', 'ASCII//TRANSLIT',  $filename);
  $preg = preg_replace('/[^\00-\255]+/u', '', $filename);

  // Process videos locally.
  $q->setStatus('processing', $index);
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
    $q->setTitle($filename, $index);
    print "\n  " . $filename;
  }
  else {
    $q->setTitle(array_shift($after), $index);
    dlog("No new downloads found");
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


  // 6. Refresh.
  $q->setStatus('refreshing', $index);
  chdir($htmlpath);

  $elapsed = time();
  $cmd = "php update.php diff " . $machine_name;
  vcmd($cmd, "Comparing files...");
  print " (" . (time() - $elapsed) . "s)";

  print "\nCollection " . $machine_name . ": " . sizeof($collections[$machine_name]['items']);

  $elapsed = time();
  $cmd = "php update.php gen " . $machine_name . " --overwrite";
  vcmd($cmd, "Writing collection...");
  print " (" . (time() - $elapsed) . "s)";

  $q->setStatus('completed', $index);

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
