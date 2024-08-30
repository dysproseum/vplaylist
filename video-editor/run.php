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
  $cmd = "yt-dlp $url";
  vcmd($cmd, "Downloading...");
  print "done.";

  // Get filename.
  $after = glob($download_dir . "/*");
  $diff = array_diff($after, $before);
  if (!empty($diff)) {
    $filename = $diff[0];
    print "\n  Saved to: $filename\n";
  }
  else {
    dlog("Downloaded file not found");
    print_r($after);
    continue;
  }
  // Clean up characters before conversion.
  $iconv = iconv('UTF-8', 'ASCII//TRANSLIT',  $filename);
  print "\niconv filename: " . basename($iconv);
  $preg = preg_replace('/[^\00-\255]+/u', '', $filename);
  print "\npreg filename: " . basename($preg) . "\n";

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
    vcmd($cmd);
    print "done.";

    // Verify import collection exist.
    $import_dir = $conf['video_dir'] . '/' . MACHINE_NAME;
    if (!is_dir($import_dir)) {
      chdir($htmlpath);
      $cmd = 'php update.php create "' . COLLECTION_NAME . '"';
      vcmd($cmd);
    }

    // Copy to import directory.
    chdir($video_editor_dir);
    $import_dir = $conf['video_dir'] . '/' . MACHINE_NAME;
    $cmd = "cp mp4/* $import_dir/";
    vcmd($cmd);

    // Remove files to prevent future copying.
    $cmd = "rm mp4/*";
    vcmd($cmd);
    // Also remove from downloads or they get regenerated...
    // Move into orig folder?
    create_dir($video_editor_dir . "/originals");
    $cmd = "mv download/* originals/";
    vcmd($cmd);

  }
}


// 6. Refresh.
print "\nCollection " . MACHINE_NAME . ": " . sizeof($collections[MACHINE_NAME]['items']);
chdir($htmlpath);
$cmd = "php update.php diff " . MACHINE_NAME;
vcmd($cmd, "Refreshing metadata...");
$cmd = "php update.php gen " . MACHINE_NAME;
if (DEBUG == 2) vcmd($cmd, "Generating collection...");
$cmd = "php update.php gen " . MACHINE_NAME . " > " . MACHINE_NAME . ".json";
vcmd($cmd, "Saving new collection...");
$cmd = "diff " . MACHINE_NAME . ".json collections/" . MACHINE_NAME . ".json";
if (DEBUG == 2) vcmd($cmd, "Running diff...");
$cmd = "cp " . MACHINE_NAME . ".json collections/";
vcmd($cmd, "Updating collection...");
print "done.";


// 7. Generate.
chdir($htmlpath);
$cmd = "php generate.php " . MACHINE_NAME;
vcmd($cmd, "Generating thumbnails...");
print "done.";
dlog("Job completed.\n");

// Delete links.txt
unlink("$p.inprogress");

// @todo notifications
