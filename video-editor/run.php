<?php

/* @todo load host paths from config.
$config_path = dirname(__FILE__) . "../vplaylist.conf";
if (file_exists($config_path)) {
  require_once($config_path);
}
else {
  print "Could not load host paths from config_path.\n";
  print "Expected: $config_path\n";
  exit;
}
*/

$p = "/mnt/uploads/video-editor/links.txt";
$htmlpath = "/home/david/docker/php-apache/php/www/pi3omv5-apache-php/html/dysproseum.com/vplaylist";
$rsync_target = 'pi@192.168.1.82:/mnt/data/overflow/vplaylist_mp4/video_editor/';

// Check for job in progress.
if (file_exists("$p.inprogress")) {
  print "\nJob still in progress, waiting to process new requests...";
  exit;
}

$urls = [];
$handle = fopen($p, "r");
if ($handle) {
    rename($p, "$p.inprogress", $handle);

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
print "\n$plural_maybe in the queue.";

foreach ($urls as $index => $url) {
  echo "\nRequest " . ($index + 1) . " of $cnt: ";
  echo $url;

  // Download.
  print "Downloading...";
  chdir("/mnt/uploads/video-editor");
  $cmd = "yt-dlp --recode-video mp4 -q --no-warnings $url";
  exec($cmd);
  print "done.";
}

// Copy.
print "\nCopying files...";
$cmd = "rsync -av --exclude=links.txt* /mnt/uploads/video-editor/ pi@192.168.1.82:/mnt/data/overflow/vplaylist_mp4/video_editor/";
shell_exec("$cmd");
print "done.";

// Refresh.
print "\nRefreshing metadata...";
chdir($htmlpath);
exec("php update.php diff video_editor");
exec("php update.php gen video_editor > video_editor.json");
exec("diff video_editor.json collections/video_editor.json");
exec("cp video_editor.json collections/");
print "done.";

// Generate.
print "\nGenerating thumbnails...";
chdir($htmlpath);
exec("php generate.php video_editor");
print "complete.\n";

// @todo Delete links.txt if successful :-/
unlink("$p.inprogress");

// @todo Email notifications

// @todo Run ffmpeg to combine
// ??
