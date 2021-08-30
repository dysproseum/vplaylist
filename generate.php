<?php

require_once 'include/bootstrap.php';

foreach ($collections as $name => $items) {

	$out_dir = THUMBS_PATH . $name;
	exec('mkdir -p ' . $out_dir);

	foreach ($collections[$name]['items'] as $item) {

		$input = '/mnt/' . $item['filename'];
		$output = $out_dir . '/' .  basename($input, '.mp4') . '.jpg';

		$ffmpeg = 'ffmpeg -ss 00:00:01.00 -i "' . $input . '" -vf \'scale=320:320:force_original_aspect_ratio=decrease\' -vframes 1 "' . $output . '"';

		print("\n" .$ffmpeg);
		exec($ffmpeg);
	}
}
#ffmpeg -loglevel error -ss 00:00:01.00 -i "$line" -vf 'scale=320:320:force_original_aspect_ratio=decrease' -vframes 1 "$OUT_DIR/`basename "$line"`.jpg"
