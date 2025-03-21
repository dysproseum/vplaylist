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
$p = $video_editor_dir . "/jobs.json";

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

// Process queued jobs.
foreach ($queue as $link) {
  if (!isset($link['id'])) {
    continue;
  }
  $id = $link['id'];

  // Process videos locally.
  $q->setStatus('processing', $id);
  $mp4_dir = $video_editor_dir . "/mp4";
  $before = glob($mp4_dir . "/*");
  $elapsed = time();
  chdir($video_editor_dir);

  $edit = $link['edit'];
  print "\n  [Slot $id] [Edit type: $edit]";

  // IF CLIP EDIT
  if ($edit == "clip") {

    // Get details.
    $machine_name = $link['collection'];
    $source = $link['player'];
    $item = $collections[$machine_name]['items'][$source];
    $filename = $item['filename'];
    $title = basename($filename, '.mp4');
    dlog("Filename: $filename");
    // get $edit_start
    $edit_start = $link['player-mark-in-value'];
    // get $edit_end
    $edit_end = $link['player-mark-out-value'];
    // set $output filename
    $output = "$mp4_dir/$title " . date('Y-m-d_h.i.s') . '.mp4';

    // https://stackoverflow.com/questions/18444194/cutting-multimedia-files-based-on-start-and-end-time-using-ffmpeg
    // $cmd = "ffmpeg -i input.mp4 -ss 5.5 -t 4.75 -c copy output.mp4";
    $cmd = "ffmpeg -ss $edit_start -to $edit_end -i \"$filename\" -c copy \"$output\"";
    dlog($cmd);

    print "\nProcessing media...";

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
    // $q->setProgress($duration, "1", $id);
    pclose($proc);
    print " (" . (time() - $elapsed) . "s)";
  }

  // ELSE IF ASSEMBLE EDIT
  elseif ($edit == "assemble") {
    dlog("Assemble");

    $machine_name = $link['collection'];
    $source = $link['player'];
    $source_item = $collections[$machine_name]['items'][$source];
    $source_filename = $source_item['filename'];

    $target = $link['recorder'];
    $target_item = $collections[$machine_name]['items'][$target];
    $target_filename = $target_item['filename'];
    $title = basename($target_filename, '.mp4');

    dlog("Working title: $title");

    $source_start = $link['player-mark-in-value'];
    $source_end = $link['player-mark-out-value'];
    $target_start = $link['recorder-mark-in-value'];
    $edit_end = $link['recorder-mark-out-value'];

    $output = "$mp4_dir/$title " . date('Y-m-d_h.i.s') . '.mp4';

    // https://creatomate.com/blog/how-to-join-multiple-videos-into-one-using-ffmpeg
    // $cmd = ffmpeg -i video1.mp4 -i video2.mp4 -filter_complex "[0:v][0:a][1:v][1:a]concat=n=2:v=1:a=1" -vsync vfr output.mp4
    $cmd = "ffmpeg -i \"$target_filename\" -i \"$source_filename\" -filter_complex \"[0:v][0:a][1:v][1:a]concat=n=2:v=1:a=1\" -vsync vfr \"$output\"";

    dlog($cmd);

    print "\nProcessing media...";

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
    // $q->setProgress($duration, "1", $id);
    pclose($proc);
    print " (" . (time() - $elapsed) . "s)";
  }

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
  //$cmd = "mv download/* originals/";
  //vcmd($cmd);

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
