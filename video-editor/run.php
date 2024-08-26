<?php

require_once dirname(__FILE__) . '/../include/bootstrap.php';
global $collections;
global $conf;

define('COLLECTION_NAME', $conf['import_collection']);
define('MACHINE_NAME', machine_name(COLLECTION_NAME));
define('EXTERNAL_MEDIA', $conf['external_media']);
define('MEDIA_HOSTNAME', $conf['media_hostname']);
define('STORE_HOSTNAME', $conf['store_hostname']);
define('STORE_TARGET', $conf['store_target']);

// Define directories.
$video_editor_dir = $conf['video_dir'] . "/video-editor";
$p = $video_editor_dir . "/links.txt";
// We know this should be the parent directory.
$htmlpath = dirname(__FILE__) . "/../";
// Rsync optional, ex. files stored on a NAS.
$rsync_target = STORE_HOSTNAME . ':' . STORE_TARGET;


/***************************************************/


// 1. Check for job in progress.
if (file_exists("$p.inprogress")) {
  error_log("\nJob still in progress, waiting to process new requests...");
  exit;
}

// 2. Check pending requests.
$urls = [];
if (!file_exists($p)) {
  exit;
}
rename($p, "$p.inprogress");
$handle = fopen("$p.inprogress", "r");
if ($handle) {

    while (($line = fgets($handle)) !== false) {
        $urls[] = $line;
    }

    fclose($handle);
} else {
    error_log("Error opening $p");
    exit;
}
$plural_maybe = "no requests";
$cnt = sizeof($urls);
if ($cnt == 0) {
  print "\nQueue contained empty line";
}
else if ($cnt == 1) {
  $plural_maybe = "There is 1 request";
}
else {
  $plural_maybe = "There are $cnt requests";
}
dlog("$plural_maybe in the queue.");

// 3. Download.
foreach ($urls as $index => $url) {
  $numeral = $index + 1;
  echo "\n  [$numeral/$cnt] $url";

  print "\nDownloading...";
  chdir($video_editor_dir . "/download");
  $cmd = "yt-dlp $url";

  // Run command and log errors.
  $output = [];
  $result = exec("$cmd 2>&1", $output, $result_code);
  if ($result_code != 0) {
    print_r($output);
  }
  print "done.";

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
  else {
    // Process videos locally.
    print "\nProcessing media...";
    $cmd = "cd $video_editor_dir && ./collect_mp4.sh";
    shell_exec($cmd);
    print "done.";

    // Verify import collection exist.
    chdir($htmlpath);
    if (!isset($collection[MACHINE_NAME])) {
      $cmd = 'php update.php create "' . COLLECTION_NAME . '"';
      exec($cmd);
    }

    // Copy to import directory.
    chdir($video_editor_dir);
    $import_dir = $conf['video_dir'] . '/' . MACHINE_NAME;
    $cmd = "cp mp4/* $import_dir/";
    exec($cmd);
  }
}


// 6. Refresh.
print "\nRefreshing metadata...";
chdir($htmlpath);
$cmd = "php update.php diff " . MACHINE_NAME;
exec($cmd);
$cmd = "php update.php gen " . MACHINE_NAME;
exec($cmd);
$cmd = "php update.php gen " . MACHINE_NAME . " > " . MACHINE_NAME . ".json";
exec($cmd);
$cmd = "diff " . MACHINE_NAME . ".json collections/" . MACHINE_NAME . ".json";
exec($cmd);
$cmd = "cp " . MACHINE_NAME . ".json collections/";
exec($cmd);
print "done.";


// 7. Generate.
print "\nGenerating thumbnails...";
chdir($htmlpath);
$cmd = "php generate.php " . MACHINE_NAME;
exec($cmd);
print "done.";
dlog("Job completed.\n");

// Delete links.txt
// @todo only if successful
unlink("$p.inprogress");

// @todo Email notifications
