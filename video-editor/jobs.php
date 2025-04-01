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
  // $project = $link['project'];
  $project = '18fe23866ff04783cc274263373a24a8';
  $result = preg_replace("/[^a-zA-Z0-9]+/", "", $project);

  // Process videos locally.
  $q->setStatus('processing', $id);
  $mp4_dir = $video_editor_dir . "/projects/$project";
  dlog($mp4_dir);
  if (!is_dir($mp4_dir)) {
    mkdir($mp4_dir);
  }

  $before = glob($mp4_dir . "/*");
  $elapsed = time();
  chdir($video_editor_dir);

  $edit = $link['edit'];
  print "\n  [Slot $id] [Edit type: $edit]";

  //---------------------------------------------------------------------------
  // CLIP EDIT
  //
  // Clip one video down into a smaller target video.
  //---------------------------------------------------------------------------
  if ($edit == "clip") {

    // Get details.
    $machine_name = $link['player_collection'];
    $source = $link['player_id'];
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
    exec($cmd);

    print "\nProcessing media...";

  }

  //--------------------------------------------------------------------------
  // ASSEMBLE EDIT
  //
  // Add the source video+audio onto the end of the target video.
  //---------------------------------------------------------------------------
  elseif ($edit == "assemble") {

    $machine_name1 = $link['player_collection'];
    $source = $link['player_id'];
    $source_item = $collections[$machine_name1]['items'][$source];
    $source_filename = $source_item['filename'];

    $machine_name2 = $link['recorder_collection'];
    $target = $link['recorder_id'];
    $target_item = $collections[$machine_name2]['items'][$target];
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
    exec($cmd);

    print "\nProcessing media...";

  }

  //---------------------------------------------------------------------------
  // INSERT MODE
  //
  // Replace the audio or video track of marked target with marked source
  //---------------------------------------------------------------------------
  elseif ($edit == "insert") {
    $video_insert = isset($link['video-insert']) ? $link['video-insert'] : false;
    $audio_insert = isset($link['audio-insert']) ? $link['audio-insert'] : false;
    dlog("Insert Mode");

    $machine_name1 = $link['player_collection'];
    $source = $link['player_id'];
    $source_item = $collections[$machine_name1]['items'][$source];
    $source_filename = $source_item['filename'];

    $machine_name2 = $link['recorder_collection'];
    $target = $link['recorder_id'];
    $target_item = $collections[$machine_name2]['items'][$target];
    $target_filename = $target_item['filename'];
    $title = basename($target_filename, '.mp4');

    dlog("Working title: $title");

    $source_start = $link['player-mark-in-value'];
    $source_end = $link['player-mark-out-value'];
    $target_start = $link['recorder-mark-in-value'];
    $target_end = $link['recorder-mark-out-value'];

    // @todo validate and compute which diff
    if ($source_end == "") {
      $diff_target = time_code_to_seconds($target_end) - time_code_to_seconds($target_start);
      dlog("Diff_target $diff_target");
      $end_seconds = time_code_to_seconds($source_start) + $diff_target;
      $source_end = seconds_to_time_code($end_seconds);
    }
    else if ($target_end == "") {
      $diff_target = time_code_to_seconds($source_end) - time_code_to_seconds($source_start);
      dlog("Diff_target $diff_target");
      $end_seconds = time_code_to_seconds($target_start) + $diff_target;
      $target_end = seconds_to_time_code($end_seconds);
    }

    $output = "$mp4_dir/$title " . date('Y-m-d_h.i.s') . '.mp4';

    // video insert needs multiple steps
    // cut the first part from the target
    //  clip > tmp1
    $cmd = "ffmpeg -ss 00:00:00 -to $target_start -i \"$target_filename\" -c copy tmp1.mp4";
    dlog($cmd);
    exec($cmd);

    // cut the end part from the target
    //  clip > tmp3
    $cmd = "ffmpeg -ss $target_end -i \"$target_filename\" -c copy tmp3.mp4";
    dlog($cmd);
    exec($cmd);

    // cut the middle part from the target
    //  clip > tmp2
    $cmd = "ffmpeg -ss $target_start -to $target_end -i \"$target_filename\" -c copy tmp2.mp4";
    dlog($cmd);
    exec($cmd);

    // overlay the track from source onto middle target
    if ($video_insert && $audio_insert) {
      // @todo assemble edit?
    }
    elseif ($video_insert) {
      // clip the source into tmp2a.mp4
      $cmd = "ffmpeg -ss $source_start -to $source_end -i \"$source_filename\" -c:v libx264 -c:a aac tmp2a.mp4";
      dlog($cmd);
      exec($cmd);

      // $cmd = "ffmpeg -i tmp2.mp4 -i \"$source_filename\" -ss $source_start -to $source_end -filter_complex \"overlay=0:0\" tmp4.mp4";
      $cmd = "ffmpeg -i tmp2.mp4 -i tmp2a.mp4 -c:v libx264 -c:a aac -map 1:v -map 0:a -shortest tmp4.mp4";
      dlog($cmd);
      // @todo show progress on this command
      exec($cmd);
    }
    elseif ($audio_insert) {
      // clip the source audio into tmp2a.mp4
      $cmd = "ffmpeg -ss $source_start -to $source_end -i \"$source_filename\" -c copy tmp2a.mp4";
      dlog($cmd);
      exec($cmd);

      // just do a video insert from tmp2.mp4 into tmp4.mp4
      // $cmd = "ffmpeg -i tmp2a.mp4 -i tmp2.mp4 -c:v copy -map 1:v -map 0:a tmp4.mp4";
      $cmd = "ffmpeg -i tmp2a.mp4 -i tmp2.mp4 -c:v copy -c:a copy -map 1:v -map 0:a tmp4.mp4";
      dlog($cmd);
      exec($cmd);
    }

    // @todo audio mixing
    // $cmd = ffmpeg -i file_1.mp3 -i file_2.mp3 -filter_complex [0:a:0][1:a:0]amix=inputs=2:duration=longest[aout] -map [aout] "file_out.ogg"

    // recombine
    //   assemble with tmp1 + tmp4 + tmp3
    $cmd = "ffmpeg -i tmp1.mp4 -i tmp4.mp4 -i tmp3.mp4 -filter_complex \"[0:v][0:a][1:v][1:a][2:v][2:a]concat=n=3:v=1:a=1\" -vsync vfr \"$output\"";
    dlog($cmd);
    exec($cmd);

    // remove tmp files
    $cmd = "rm tmp1.mp4 tmp2.mp4 tmp2a.mp4 tmp3.mp4 tmp4.mp4";
    dlog($cmd);
    exec($cmd);

    print "\nProcessing media...";
    print " (" . (time() - $elapsed) . "s)";

  }

  //---------------------------------------------------------------------------
  // AUDIO DUB
  //
  // Replace the audio of target video with audio from source video
  //---------------------------------------------------------------------------
  elseif ($edit == "dub") {
    dlog("Audio Dub");

    $machine_name1 = $link['player_collection'];
    $source = $link['player_id'];
    $source_item = $collections[$machine_name1]['items'][$source];
    $source_filename = $source_item['filename'];

    $machine_name2 = $link['recorder_collection'];
    $target = $link['recorder_id'];
    $target_item = $collections[$machine_name2]['items'][$target];
    $target_filename = $target_item['filename'];
    $title = basename($target_filename, '.mp4');

    dlog("Working title: $title");

    // no need for timestamps
    $output = "$mp4_dir/$title " . date('Y-m-d_h.i.s') . '.mp4';

    // copy over with shortest
    $cmd = "ffmpeg -i \"$source_filename\" -i \"$target_filename\" -c:v copy -c:a copy -map 0:a -map 1:v -shortest \"$output\"";
    dlog($cmd);
    exec($cmd);

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
    $q->setError("Downloaded file not found", $id);
    exit;
  }

  $machine_name = 'video_editor';
  $import_dir = $conf['video_dir'] . '/' . $machine_name;
  // Move converted files to data directory?
  chdir($mp4_dir);
  $cmd = "mv * $import_dir/";
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
