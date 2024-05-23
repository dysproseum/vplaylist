<?php

require_once dirname(__FILE__) . '/include/bootstrap.php';
global $collections;
$dir = $conf['video_dir'];

if (isset($_REQUEST['index'])) {
  $index = $_REQUEST['index'];
  $machine_name = $_REQUEST['collection'];
  $item = $collections[$machine_name]['items'][$index];
  $filepath = $item['filename'];
}
else {
  header('HTTP/1.1 404 Not found');
  exit;
}

$filename = basename($filepath);
$filesize = filesize($filepath);
$offset = 0;
$length = $filesize;
$buffer_size = 1024 * 1024;

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
