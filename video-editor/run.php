<?php

// We know this should be the parent directory.
$htmlpath = dirname(__FILE__) . "/../";
chdir($htmlpath);
require_once 'include/bootstrap.php';
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
// Rsync optional, ex. files stored on a NAS.
$rsync_target = STORE_HOSTNAME . ':' . STORE_TARGET;

/***************************************************/

// 1. Check for job in progress.
if (file_exists("$p.inprogress")) {
  print ".";
  if (DEBUG == 2) {
    dlog("[DEBUG] Job still in progress, waiting to process new requests...");
  }
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
    dlog("Error opening $p");
    exit;
}
$plural_maybe = "no requests";
$cnt = sizeof($urls);
if ($cnt == 0) {
  dlog("Queue contained empty line");
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
  print "\n  [$numeral/$cnt] $url";

  $download_dir = $video_editor_dir . "/download";
  $before = glob($download_dir . "/*");
  chdir($download_dir);

  $elapsed = time();
  $cmd = "yt-dlp $url";
  vcmd($cmd, "Downloading...");
  print " (" . (time() - $elapsed) . "s)";

  // Get filename.
  $after = glob($download_dir . "/*");
  $diff = array_diff($after, $before);
  if (!empty($diff)) {
    $filename = $diff[0];
    print "\n  " . basename($filename);
  }
  else {
    dlog("Downloaded file not found");
    print_r($after);
    continue;
  }

  // @todo clean up characters before conversion.
  $iconv = iconv('UTF-8', 'ASCII//TRANSLIT',  $filename);
  $preg = preg_replace('/[^\00-\255]+/u', '', $filename);

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
    $elapsed = time();
    $cmd = "cd $video_editor_dir && ./collect_mp4.sh";
    vcmd($cmd, "Processing media...");
    print " (" . (time() - $elapsed) . "s)";

    // Verify import collection exist.
    $import_dir = $conf['video_dir'] . '/' . MACHINE_NAME;
    if (!is_dir($import_dir)) {
      chdir($htmlpath);
      $cmd = 'php update.php create "' . COLLECTION_NAME . '"';
      vcmd($cmd);
    }

    // Move converted files to data directory.
    chdir($video_editor_dir);
    $import_dir = $conf['video_dir'] . '/' . MACHINE_NAME;
    $cmd = "mv mp4/* $import_dir/";
    vcmd($cmd);

    // Move downloads into originals folder or they get regenerated.
    $cmd = "mv download/* originals/";
    vcmd($cmd);
  }
}


// 6. Refresh.
chdir($htmlpath);

$elapsed = time();
$cmd = "php update.php diff " . MACHINE_NAME;
vcmd($cmd, "Comparing files...");
print " (" . (time() - $elapsed) . "s)";

print "\nCollection " . MACHINE_NAME . ": " . sizeof($collections[MACHINE_NAME]['items']);

$elapsed = time();
$cmd = "php update.php gen " . MACHINE_NAME . " --overwrite";
vcmd($cmd, "Writing collection...");
print " (" . (time() - $elapsed) . "s)";


// 7. Generate.
chdir($htmlpath);
$elapsed = time();
$cmd = "php generate.php " . MACHINE_NAME;
vcmd($cmd, "Generating thumbnails...");
print " (" . (time() - $elapsed) . "s)";

dlog("Job completed.\n");

// Delete links.txt
unlink("$p.inprogress");

// @todo notifications
