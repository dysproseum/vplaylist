<?php

require_once dirname(__FILE__) . '/include/bootstrap.php';
global $collections;
$dir = $conf['video_dir'];

if (!isset($_REQUEST['index'])) {
  header('HTTP/1.1 404 Not found');
  exit;
}

$index = $_REQUEST['index'];
$machine_name = $_REQUEST['collection'];
$item = $collections[$machine_name]['items'][$index];

// Serve thumbnails and exit.
if (isset($_REQUEST['file']) && $_REQUEST['file'] == '.jpg') {
  $filepath = $item['thumbnail'];
  if (!file_exists($filepath)) {
    $filepath = dirname(__FILE__) . "/include/videotape.png";
  }
  $filesize = filesize($filepath);
  header('X-Vplaylist: ' . $filepath);

  $fp = fopen($filepath, 'rb');
  if ($fp) {
    $mtime = filemtime($filepath);
    header('Content-Type: ' . mime_content_type($filepath));
    header('Content-Length: ' . $filesize);

    // Caching.
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $mtime));
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      $last = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
      if ($mtime >= $last) {
        header('HTTP/1.1 304 Not Modified');
      }
    }

    fpassthru($fp);
    fclose($fp);
  }
  else {
    header('HTTP/1.1 404 Not found');
  }
  exit;
}

$filepath = $item['filename'];
$filesize = filesize($filepath);
$filename = basename($filepath);

// Prepare to serve video.
$offset = 0;
$length = $filesize;
$buffer_size = 1024 * 1024;
if (isset($conf['buffer_size'])) {
  $buffer_size = $conf['buffer_size'];
}

$fp = fopen($filepath, "rb");
if (!$fp) {
  header('HTTP/1.1 404 Not found');
  exit;
}

// Allow seeking.
header("Accept-Ranges: bytes");
if (isset($_SERVER['HTTP_RANGE'])) {
  preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
  $offset = intval($matches[1]);
  if (!isset($matches[2])) {
	  $end = $offset + $buffer_size;
	  if ($end > $filesize - 1) {
		  $end = $filesize - 1;
	  }
  }
  else {
	  $end = $matches[2];
  }
  $length = $end + 1 - $offset;
  header ("HTTP/1.1 206 Partial content");
  header("Content-Range: bytes $offset-$end/$filesize");
}

// Create M3U wrapper for mobile playback.
if (!isset($_REQUEST['file'])) {
	$op = '';
	$op .= "#EXTM3U\n";
	$op .= "#EXTINF:292,$file\n";
	$op .= "https://dysproseum.com/vplaylist/serve.php?filename=$filename&file=.mp4";

	header('Connection: Keep-Alive');
	header('Content-Disposition: inline; filename=vplaylist.m3u');
	header('Content-Type: audio/x-mpegurl');
	header('Content-Length: ' . (string) sizeof($op));

	print $op;
	exit;
}

header('Content-Disposition: attachment');
header('Content-Type: video/mp4');
header('Content-Transfer-Encoding: binary');
header("Content-Length: $length");

fseek($fp, $offset);
while (!feof($fp)) {
	$buffer = fread($fp, 32 * 1024);
	print $buffer;
}
fclose($fp);
