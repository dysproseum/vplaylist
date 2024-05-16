<?php

// See also bootstrap.php:vlog().
define('DEBUG', false);

require_once 'include/bootstrap.php';
global $collections;
$htmlpath = dirname(__FILE__);

if (isset($argv[1])) {
  $action = $argv[1];
}
global $action;

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
  print "Usage: php update.php [diff|gen|create|list] [collection_id]\n\n";
  print "  gen --all           Update all collections.\n";
  print "  create \"New Vids\"   Create new collection.\n";
  exit;
}

if ($action == "list") {
  dlog("Listing...");
  exit;
}
else if ($action == "create") {
  // Sanitize collection name.
  $collection_name = $argv[2];
  $machine_name = strtolower($collection_name);
  $machine_name = preg_replace('/[^\w\s]+/', '', $machine_name);
  $machine_name = preg_replace('/[^a-zA-Z0-9]+/', '_', $machine_name);

  if (!$machine_name) {
    exit("Invalid collection name, use a-z and 0-9 only.\n");
  }

  dlog("Creating " . $collection_name . " as " . $machine_name . "...");
  $new_file = $machine_name . ".json";

  $new_collection = [
    $machine_name => [
      'name' => $collection_name,
      'items' => [],
    ],
  ];
  $new_json = json_encode($new_collection, JSON_PRETTY_PRINT);

  $path = $htmlpath . "/collections/" . $new_file;
  $handle = fopen($path, "w");
  if ($handle) {
    fwrite($handle, $new_json);
    dlog("Created " . $path);
  }
  else {
    exit('Could not write to ' . $path . "\n");
  }

  $new_dir = $conf['video_dir'] . '/' . $machine_name;
  if (!is_dir($new_dir)) {
    if (mkdir($new_dir)) {
      dlog("Directory $new_dir created.");
    }
    else {
      dlog("Directory $new_dir failed to create.");
    }
  }
  else {
    dlog("Directory $new_dir already exists.");
  }
  dlog("Done.\n");
  exit;
}

foreach ($machine_names as $name) {

  // Grab the directory from filename path.
  $collection_path = $conf['video_dir'] . '/' . $name;

  vlog("Collection: $name\n");
  vlog("Collection path (app): $collection_path\n");
  $dir = $collection_path;
  vlog("Directory to list (host): $dir\n");
  $imm_files = glob($dir.'/*.m*');
  vlog("Found files:    " . sizeof($imm_files) . "\n");
  vlog("Existing items: " . sizeof($collections[$name]['items']) . "\n");
  $mod_files = $imm_files;

  // Find new files by removing existing items from the array.
  foreach ($mod_files as $index => $file) {
    if (DEBUG) vlog("\n $index. $file ");

    foreach ($collections[$name]['items'] as $item) {
      if (basename($item['filename']) == basename($file)) {
        if (DEBUG) vlog("\n MATCH");

        unset($mod_files[$index]);
      }
    }
  }
  vlog("Leftover files: " . sizeof($mod_files));

  // Leftover unmatched files are added to the collection array.
  foreach ($mod_files as $index => $filename) {
    // reuse logic from install.php
    $filename = $collection_path . '/' . basename($filename);
    $filename = $dir . '/' . basename($filename);
    //$filesize = sprintf("%u", filesize($dir . '/' . basename($filename)));
    $filesize = exec('stat -c %s "' . $filename . '"');
    //if ((int) $filesize < 0) {
    //  $filesize = exec("stat -c %s " . $filename);
      //$filesize = sprintf("%u", $filesize + PHP_INT_MAX + PHP_INT_MAX + 2);
    //}

    $build = array(
      'filename' => $collection_path . '/' . basename($filename),
      'size' => $filesize,
      'length' => FALSE,
      'thumbnail' => FALSE,
    );
    $collections[$name]['items'][] = $build;
  }

  // Show any deleted files by 
  // Loop again through collections and compare to file list.
  $mod_items = $collections[$name]['items'];
  if (DEBUG) vlog("\n\nDeleted files:");

  foreach ($mod_items as $index => $item) {
    if (DEBUG) vlog("\n $index. " . basename($item['filename']));

    // If filename from directory matches one in the json, skip it.
    // We only want to show items whose files may have been deleted.
    foreach ($imm_files as $filename) {
      if (basename($filename) == basename($item['filename'])) {
        unset($mod_items[$index]);
      }
    }
  }
  vlog("\nOld/deleted files: " . sizeof($mod_items) . "\n");

  // Old/deleted files are removed from the collection array.
  foreach ($collections[$name]['items'] as $index => $collection_item) {
    foreach ($mod_items as $item) {
      if ($item['filename'] == $collection_item['filename']) {
        unset($collections[$name]['items'][$index]);
      }
    }
  }

//  if ($action == "diff") {

  // If no modifications, continue to next collection, if any.
  if ($mod_items) {
    vlog("\nTotal Old files: " . sizeof($mod_items));
    if (sizeof($mod_items) > 0) {
      foreach ($mod_items as $item) {
        vlog("\n  " . $item['filename']);
      }
    }
    vlog("\n");
  }

  if ($mod_files) {
    vlog("\nNew files: " . sizeof($mod_files));
    if (sizeof($mod_files) > 0) {
      $mod_files = array_reverse($mod_files);
      foreach ($mod_files as $file) {
        vlog("\n  $file");
      }
    }
    vlog("\n");
  }
  vlog("\n");
}

//  } // action == diff
  
  // @todo Convert new files to web mp4 format.

  if ($action == "gen") {
    // Output updated json file for this collection.
    $collections[$name]['items'] = array_reverse($collections[$name]['items']);
    $out = array($name => $collections[$name]);
    $json = json_encode($out, JSON_PRETTY_PRINT);
    print $json . "\n";
    exit;
  }
