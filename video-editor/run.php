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
if (!$q) {
  dlog("Failed to instantiate new Queue");
  exit;
}
$q->pruneCompleted();

// 2. If job in progress, indicate progress in log file.
$links = $q->getActiveLinks();
if (!empty($links)) {
  print ".";
  exit;
}

// Nothing to do.
$queue = $q->queueLink();
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
  $title = str_replace('\'', '', $title);
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
  $cmd = "yt-dlp --progress --newline -o \"$title.%(ext)s\" " . $link['url'];
  print "\nDownloading...";
  $q->setStatus('downloading', $id);

  /*
  [download] 100.0% of ~   712.00B at    2.33KiB/s ETA Unknown (frag 0/37)
  [download]   1.4% of ~  51.45KiB at    2.33KiB/s ETA Unknown (frag 1/37)
  [download] 100.0% of ~ 116.10MiB at   10.52MiB/s ETA 00:00 (frag 37/37)
  [download]  99.0% of ~ 117.33MiB at   10.52MiB/s ETA 00:00 (frag 38/37)
  */

  // Follow command output for progress.
  while (@ob_end_flush()); // end all output buffers if any
  $proc = popen("$cmd 2>&1", 'r');
  if (!$proc) {
    dlog("Failed to open command for reading: $cmd");
  }
  else {
    $progress = 0;
    $use_frag = false;
    while ($line = fgets($proc, 4096)) {
      if (strstr($line, "[download]") && !strstr($line, "Destination")) {
        // Percentages can vary for each fragment.
        $frag_pos = strpos($line, "frag");
        if ($frag_pos) {
          $use_frag = true;
          $frag_pos += 5;
          $frag_slash = strpos($line, "/", $frag_pos);
          $frag_paren = strpos($line, ")", $frag_pos);

          $frag = intval(substr($line, $frag_pos, $frag_slash - $frag_pos));
          $frag_total = intval(substr($line, $frag_slash + 1, $frag_paren - $frag_slash));

          if ($frag > $progress && $frag_total >= $frag) {
            if (DEBUG == 2) echo "frag: [$frag]/[$frag_total]\n";
            $q->setProgress($frag / $frag_total, $frag_total - $frag, $id);
            $progress = $frag;
          }
        }
        else if ($use_frag == false) {
          // Fallback to percentages if no frag
          $percent = trim(substr($line, 11, 5));
          $percent = str_replace('%', '', $percent);
          $eta = 100 - (float) $percent;

          if ($percent > $progress) {
            if (DEBUG == 2) echo "Percent: $percent\n";
            $q->setProgress($percent / 100, $eta, $id);
            $progress = $percent;
          }
        }
        else {
          // Skip progress on extra format frags at the end.
        }
      }
      @flush();
    }
  }
  pclose($proc);
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
    // But the downloaded file may have been leftover.
    // @todo clear the downloads at the beginning?
    $q->setError("Downloaded file not found", $id);
    // continue;
    exit;
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
        if (strstr($speed, 'fps')) {
          $speed = 0;
        }
      }
      if (strstr($line, "out_time=")) {
        $min_sec = explode('=', $line)[1];
        $seconds = clock_time_to_seconds(substr($min_sec, 0, 8));
      }
      if ($speed != 0 && $seconds != 0) {
        $result = $q->setProgress($seconds, $speed, $id);
        if (!$result) {
          dlog("Failed to setProgress $seconds $speed");
          $q->setError("Failed to setProgress", $id);
          exit;
        }
        $speed = 0;
        $seconds = 0;
      }

      @flush();
    }
  }
  $q->setProgress($duration, "1", $id);
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
