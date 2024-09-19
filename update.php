<?php

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
else if ($action != "list") {
  print "Usage: php update.php [diff|gen|create|list] [collection_id]\n\n";
  print "  create \"New Vids\"   Create new collection.\n";
  print "  gen --all           Display JSON for collection(s).\n";
  print "  diff new_vids       Show files to be added or removed.\n";
  print "  gen --overwrite     Update the collection file on disk.\n";
  exit;
}

if ($action == "list") {
  foreach ($collections as $name => $object) {
    print $object['name'] . " (" . $name . ") " . sizeof($object['items']);
    print PHP_EOL;
  }
  exit;
}
else if ($action == "create") {
  // Sanitize collection name.
  $collection_name = $argv[2];
  $machine_name = machine_name($collection_name);

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

  // Create collections directory.
  $new_dir = $htmlpath . "/collections";
  create_dir($new_dir);

  // Create JSON file.
  $path = $htmlpath . "/collections/" . $new_file;
  $handle = fopen($path, "w");
  if ($handle) {
    fwrite($handle, $new_json);
    dlog("Created " . $path);
  }
  else {
    exit('Could not write to ' . $path . "\n");
  }

  // Create new media directory.
  $new_dir = $conf['video_dir'] . '/' . $machine_name;
  create_dir($new_dir);
  dlog("Done.\n");
  exit;
}

// Actions for diff & gen.
// Messages are hidden by vlog if $action == gen.
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
    if (DEBUG == 2) vlog("\n $index. $file ");

    foreach ($collections[$name]['items'] as $item) {
      if (basename($item['filename']) == basename($file)) {
        if (DEBUG == 2) vlog("\n MATCH");

        unset($mod_files[$index]);
      }
    }
  }
  vlog("Leftover files: " . sizeof($mod_files));

  // Leftover unmatched files are added to the collection array.
  foreach ($mod_files as $index => $filename) {
    $filename = $dir . '/' . basename($filename);
    $filesize = exec('stat -c %s "' . $filename . '"');

    $build = array(
      'filename' => $collection_path . '/' . basename($filename),
      'size' => $filesize,
      'length' => FALSE,
      'thumbnail' => FALSE,
      'title' => basename($filename),
      'timestamp' => filemtime($filename),
    );
    $collections[$name]['items'][] = $build;
  }

  // Show any deleted files by 
  // Loop again through collections and compare to file list.
  $mod_items = $collections[$name]['items'];
  if (DEBUG == 2) vlog("\n\nDeleted files:");

  foreach ($mod_items as $index => $item) {
    if (DEBUG == 2) vlog("\n $index. " . basename($item['filename']));

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

// Add thumbnails.
foreach ($collections[$name]['items'] as &$item) {
  $info = pathinfo($item['filename']);
  $item['thumbnail'] = $htmlpath . '/thumbnails/' . $name . '/' . $info['filename'] . '.jpg';
  $item['title'] = $info['filename'];
}

if ($action == "gen") {
  // Sort by timestamp.
  $items = $collections[$name]['items'];
  usort($items, 'date_compare');
  $items = array_reverse($items, true);
  $collections[$name]['items'] = $items;

  $out = array($name => $collections[$name]);
  $json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

  if (isset($argv[3]) && $argv[3] == "--overwrite") {
    // Write out new json file.
    $target = $htmlpath . '/collections/' . $name . '.json';
    $fp = fopen($target, 'wb');
    if ($fp) {
      fputs($fp, $json);
      fclose($fp);
    }
    // Regenerate thumbnails on update:
    // If files were added or removed they can be outdated.
    chdir($htmlpath);
    $cmd = "php generate.php $name -y";
    vcmd($cmd);
  }
  else {
    // Output updated json preview.
    print $json . "\n";
  }
  exit;
}
