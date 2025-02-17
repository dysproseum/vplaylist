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
$queue = [];
$q = new Queue($p);
$q->pruneCompleted();
$queue = $q->queueLink();
$links = $q->getActiveLinks();

// 2. If job in progress, indicate progress in log file.
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
  $q->setStatus('downloading', $id);

  // Get duration.
  $cmd = "yt-dlp --get-duration " . $link['url'];
  $min_sec = exec($cmd);
  $q->setDisplayDuration($min_sec, $id);
  $duration = clock_time_to_seconds($min_sec);
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

  $elapsed = time();
  // @todo --progress --newline?
  $cmd = "yt-dlp -o \"$title.%(ext)s\" " . $link['url'];
  vcmd($cmd, "Downloading...");
  print " (" . (time() - $elapsed) . "s)";

  // Get downloaded filename.
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
  chdir($video_editor_dir);
  print "\nProcessing media...";
  $cmd = "./collect_mp4.sh";

  // Follow command output for progress.
  while (@ob_end_flush()); // end all output buffers if any
  $proc = popen("$cmd 2>&1", 'r');
  if (!$proc) {
    dlog("Failed to open command for reading: $cmd");
  }
  else {
    $speed = 0;
    $seconds = 0;
    while ($line = fgets($proc, 4096)) {
      if (strstr($line, "speed=")) {
        $speed = explode('=', $line)[1];
        $speed = trim(str_replace('x', '', $speed));
        // echo "Speed: $speed\n";
      }
      if (strstr($line, "out_time=")) {
        // echo $line;
        $min_sec = explode('=', $line)[1];
        $seconds = clock_time_to_seconds(substr($min_sec, 0, 8));
        // echo "Duration: $seconds/$duration\n";
      }
      if ($speed != 0 && $seconds != 0) {
        // echo "Speed and duration\n";
        $q->setProgress($seconds, $speed, $id);
        $speed = 0;
        $seconds = 0;
      }

      @flush();
    }
  }
  $q->setProgress($duration, 1, $id);
  pclose($proc);
  print " (" . (time() - $elapsed) . "s)";

  // Get processed filename.
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

  // Verify import collection exists.
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
  $collection_size = sizeof($collections[$machine_name]['items']);
  print "\n  Collection " . $machine_name . ": " . $collection_size;
  $q->setCollectionSize($collection_size + 1, $id);

  $elapsed = time();
  $cmd = "php update.php gen " . $machine_name . " --overwrite --progress";
  print "\nWriting collection...";

  // Follow command output for progress.
  $q->setProgress(0, 1, $id);
  while (@ob_end_flush()); // end all output buffers if any
  $proc = popen("$cmd 2>&1", 'r');
  if (!$proc) {
    dlog("Failed to open command for reading: $cmd");
  }
  else {
    $count = 0;
    while ($line = fgets($proc, 4096)) {
      if (strstr($line, "done=")) {
        $count++;
        $speed = explode('=', $line)[1];
        $speed = trim($speed);
        $q->setProgress($count, $speed, $id);
      }
    }
    @flush();
  }
  pclose($proc);
  print " (" . (time() - $elapsed) . "s)";

  // 6. Completed.
  $collections = load_collections();
  $collection_size = sizeof($collections[$machine_name]['items']);
  print "\n  Collection " . $machine_name . ": " . $collection_size;

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
