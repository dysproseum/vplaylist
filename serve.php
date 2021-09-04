<?php

if (isset($_REQUEST['filename'])) {
  $filename = $_REQUEST['filename'];
  $filepath = base64_decode($filename);
}
else {
  exit;
}

$file = basename($filepath);
$size = filesize($filepath);

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

$fp = fopen($filepath, "rb");
if ($fp) {
	header('Content-Disposition: attachment');
	header('Content-Type: video/mp4');
	header('Content-Transfer-Encoding: binary');
	header("Content-Length: $size");

	while (!feof($fp)) {
		$buffer = fread($fp, 32 * 1024);
		print $buffer;
	}
	fclose($fp);
	exit;
}
