<?php

require_once 'include/bootstrap.php';
define('DEBUG', false);

if (isset($argv[1])) {
	$action = $argv[1];
}

$machine_names = array();
if (isset($argv[2]) && $argv[2] == '--all') {
	foreach ($collections as $name => $items) {
		$machine_names[] = $name;
	}
}
else if (isset($argv[2])) {
	$machine_names[] = $argv[2];
}
else {
	print "Usage: php update.php [diff|gen] [collection_id]\n\n";
	print "  gen --all	Update all collections.\n";
	exit;
}

foreach ($machine_names as $name) {

	if ($action !== 'gen') {
		print "Collection: $name";
	}

	// read directory and compare with json file.
	$collection_path = '';
	foreach ($collections[$name]['items'] as $item) {
		$collection_path = dirname($item['filename']);
	}
	$dir = '/mnt' . $collection_path;
        if (DEBUG) print "\nCollection path: $collection_path\n";
	$files = glob($dir.'/*.m*');
	$files_copy = $files;

	foreach ($files as $index => $file) {

		if (DEBUG) {
			print "\n$index. $file ";
		}

		foreach ($collections[$name]['items'] as $item) {
			if (basename($item['filename']) == basename($file)) {
				if (DEBUG) {
					print " MATCH ";
				}
				unset($files[$index]);
			}
		}
	}

	// Added files:
	// Leftover unmatched files to collection array.
	foreach ($files as $index => $filename) {
		// reuse logic from install.php
		$filename = $collection_path . '/' . basename($filename);
		$filename = $dir . '/' . basename($filename);
		//$filesize = sprintf("%u", filesize($dir . '/' . basename($filename)));
		$filesize = exec('stat -c %s "' . $filename . '"');
		//if ((int) $filesize < 0) {
		//	$filesize = exec("stat -c %s " . $filename);
			//$filesize = sprintf("%u", $filesize + PHP_INT_MAX + PHP_INT_MAX + 2);
		//}

		$collections[$name]['items'][] = array(
			'filename' => $collection_path . '/' . basename($filename),
			'size' => $filesize,
			'length' => FALSE,
			'thumbnail' => FALSE,
		);
	}

	// Deleted files:
	// Loop again through collections and compare to file list.
	$items = $collections[$name]['items'];
	foreach ($items as $index => $item) {
		if (DEBUG) {
			print $index . '. ' . basename($item['filename']);
		}
		foreach ($files_copy as $filename) {
			if (basename($filename) == basename($item['filename'])) {
				unset($items[$index]);
			}
		}
	}
	// Remove old files from collection.
	foreach ($collections[$name]['items'] as $index => $collection_item) {
		foreach ($items as $item) {
			if ($item['filename'] == $collection_item['filename']) {
				unset($collections[$name]['items'][$index]);
			}
		}
	}

	if ($action == "diff") {
		print "\nOld files: " . sizeof($items);
		if (sizeof($items) > 0) {
			foreach ($items as $item) {
				print "\n  " . $item['filename'];
			}
		}

		print "\nNew files: " . sizeof($files);
		if (sizeof($files) > 0) {
			// @todo leftover files may need to be converted.

			foreach ($files as $file) {
				print "\n  $file";
			}
		}
		print "\n";
	}

	if ($action == "gen") {
		// Output updated json file for this collection.
		$collections[$name]['items'] = array_reverse($collections[$name]['items']);
		$out = array($name => $collections[$name]);
		$json = json_encode($out, JSON_PRETTY_PRINT);
		print $json . "\n";
	}
}
