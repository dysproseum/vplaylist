<?php

require_once 'include/bootstrap.php';

$machine_names = array();
if (isset($argv[1])) {
	$machine_names[] = $argv[1];
	$ffmpeg = 'ffmpeg -n';
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

		$cmd = $ffmpeg . ' -ss 00:00:06.00 -i "' . $input . '" -vf \'scale=320:320:force_original_aspect_ratio=decrease\' -vframes 1 "' . $output . '"';

		print("\n" .$cmd);
		exec($cmd);
	}
}
#ffmpeg -loglevel error -ss 00:00:01.00 -i "$line" -vf 'scale=320:320:force_original_aspect_ratio=decrease' -vframes 1 "$OUT_DIR/`basename "$line"`.jpg"
