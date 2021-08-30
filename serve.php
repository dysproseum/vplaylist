<?php

//define('IMAGEPATH', '/music/MusicVideosTest/mp4');

if (isset($_REQUEST['filename'])) {
  $filename = base64_decode($_REQUEST['filename']);
}
else {
  exit;
}

//$file = IMAGEPATH . '/' . $filename;
$file = $filename;
//echo "FILE: " . $file;
$size = filesize($file);
//echo "\nSIZE: " . $size;
$fp = fopen($file, "rb");

header('Content-disposition: attachment;');\
header('Content-type: video/mp4');
header("Content-length: $size");
fpassthru($fp);

exit;

