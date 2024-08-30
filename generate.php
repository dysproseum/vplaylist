<?php

require_once 'include/bootstrap.php';

$machine_names = array();
$ffmpeg = 'ffmpeg -loglevel quiet';
$ffprobe_width = 'ffprobe -loglevel error -select_streams v:0 -show_entries stream=width -of csv=s=,:p=0 ';
$ffprobe_height = 'ffprobe -loglevel error -select_streams v:0 -show_entries stream=height -of csv=s=,:p=0 ';
$ffprobe_length = 'ffprobe -loglevel error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ';

// Timestamp to capture thumbnail (default: 4 seconds).
$thumb_timestamp = '00:00:04.00';

if (isset($argv[1])) {
	$machine_names[] = $argv[1];
	$ffmpeg .= ' -n';
}
else if (isset($argv[1]) && $argv[1] == '--all') {
	$ffmpeg .= ' -y';
	foreach ($collections as $name => $items) {
		$machine_names[] = $name;
	}
}
else {
	print "Usage: php generate.php [collection_id]\n\n";
	print " --all	Regenerate thumbnails from all collections.\n";
	exit;
}

foreach ($machine_names as $name) {

	$out_dir = THUMBS_PATH . $name;
	exec('mkdir -p ' . $out_dir);

	foreach ($collections[$name]['items'] as $item) {
		$input = $item['filename'];
		$output = $out_dir . '/' .  basename($input, '.mp4') . '.jpg';

		// Determine orientation.
		$cmd = $ffprobe_width . '"' . $input . '"';
		$width = exec($cmd);
		$cmd = $ffprobe_height . '"' . $input . '"';
		$height = exec($cmd);

		// Determine duration to ensure thumbnail capture.
		$cmd = $ffprobe_length . '"' . $input . '"';
		$length = exec($cmd);

		// @todo if ($height > $width)
		$cmd = $ffmpeg . ' -ss ' . $thumb_timestamp . ' -i "' . $input . '" -vf \'scale=320:320:force_original_aspect_ratio=decrease\' -vframes 1 "' . $output . '"';
		vcmd($cmd);
	}
}
