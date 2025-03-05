<?php

class Queue {

  var $path;
  var $links;

  function __construct($path) {
    $this->path = $path;
    $this->links = [];
    if (!file_exists($this->path)) {
      $this->save(true);
      chmod($this->path, 0777);
    }
    $result = $this->load();
    if (!$result) {
      return false;
    }
  }

  function load() {
    $data = file_get_contents($this->path);

    if ($data === false) {
      error_log("Error opening queue file: " . $this->path);
      return false;
    }
    $this->links = json_decode($data, true);
    if (json_last_error() !== 0) {
      error_log(json_last_error_msg());
      return false;
    }

    // Set new id values.
    // Needs to be done right now because newly posted items don't have ids yet.
    $resave = false;
    foreach ($this->links as $index => $link) {
      if (!isset($link['id'])) {
        error_log("Invalid queue id in load: status " . $link['status']);

        if ($link['status'] == "new") {
          if (isset($this->links[$index - 1]['id'])) {
            $this->links[$index]['id'] = $this->links[$index - 1]['id'] + 1;
          }
          else {
            $this->links[$index]['id'] = $index;
          }
          $this->links[$index]['status'] = 'queued';
          $resave = true;
        }
      }
    }
    if ($resave == true) {
      return $this->save();
    }
    return true;
  }

  function save($allow_zero = false) {
    // Don't save if zero links.
    if (!$allow_zero && sizeof($this->links) == 0) {
      // error_log("Zero links in save");
      return false;
    }

    $fp = fopen($this->path, 'wb');
    if (!$fp) {
      error_log("Error opening queue file: " . $this->path);
      return false;
    }
    $json = $this->json();
    if ($json) {
      fputs($fp, $json);
      fputs($fp, PHP_EOL);
    }
    else {
      error_log("Error saving json: " . $this->path);
      return false;
    }
    fclose($fp);
    return true;
  }

  function json() {
    return json_encode($this->links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }

  // Array indices may be incorrect after pruning.
  function get($id) {
    if (!$this->links) {
      error_log("No links in get");
      return false;
    }
    if (sizeof($this->links) == 0) {
      error_log("Sizeof links is zero in get");
      return false;
    }
    foreach ($this->links as $index => $link) {
      if ($link['id'] == $id) {
        // return pointer? no
        // return new index?
        // use guid generated in run.php?
        //
        // Either way, we need to set the array by its current index.
        // $this->get(3);
        // may return $this->links[0];
        // $this->set(3 ,$link);
        // so use:
        // $this->links[$this->get(3)]['status'] = 'downloading';
        return $index;
      }
    }
    return false;
  }

  function getLinks() {
    return $this->links;
  }

  function getActiveLinks() {
    $active = [];
    $ignore = ['completed', 'queued', 'error'];
    foreach ($this->links as $index => $link) {
      if (!in_array($link['status'], $ignore)) {
        $active[] = $link;
      }
    }
    return $active;
  }

  function queueLink() {
    $this->load();
    $queue = [];
    foreach ($this->links as $index => $link) {
      // Pull unset items into queue.
      if (!isset($link['status'])) {
        $this->setStatus('queued', $index);
        $queue[] = $link;
      }
      else if ($link['status'] == 'queued') {
        $queue[] = $link;
      }
    }
    $this->save();
    return array_slice($queue, 0, 1);
  }

  function setStatus($status, $index) {
    $this->load();
    $this->links[$this->get($index)]['status'] = $status;
    $this->links[$this->get($index)]["time_$status"] = time();

    // Unset progress between states.
    unset($this->links[$this->get($index)]['progress']);
    unset($this->links[$this->get($index)]['speed']);
    $this->save();
  }

  function setProgress($progress, $speed, $index) {
    $result = $this->load();
    if (!$result) {
      error_log("Fail to load in setProgress");
      return false;
    }

    $resave = false;

    $id = $this->get($index); 
    if ($id !== false && isset($this->links[$id])) {
      if (isset($this->links[$id]['progress'])) {
        $p1 = $this->links[$id]['progress'];
        if ($p1 != $progress) {
          $this->links[$id]['progress'] = $progress;
          $resave = true;
        }
      }
      else {
        $this->links[$id]['progress'] = $progress;
        $resave = true;
      }
      if (isset($this->links[$id]['speed'])) {
        $s1 = $this->links[$id]['speed'];
        if ($s1 != $speed) {
          $this->links[$id]['speed'] = $speed;
          $resave = true;
        }
      }
      else {
        $this->links[$id]['speed'] = $speed;
        $resave = true;
      }
    }
    else {
      // error and don't save the file if no id
      error_log("No id in setProgress, looking for: " . $index . ".");
      return false;
    }

    if ($resave) {
      return $this->save();
    }
    else {
      return true;
    }
  }

  function setCollectionSize($size, $index) {
    $this->load();
    $this->links[$this->get($index)]['collection_size'] = $size;
    $this->save();
  }

  function setTitle($title, $index) {
    $this->load();
    $this->links[$this->get($index)]['title'] = $title;
    $this->save();
  }

  function setTarget($target, $index) {
    $this->load();
    $this->links[$this->get($index)]['target'] = $target;
    $this->save();
  }

  function setIndex($collection_index, $queue_index) {
    $this->load();
    $this->links[$this->get($queue_index)]['index'] = $collection_index;
    $this->save();
  }

  function getIndex($queue_index) {
    $this->load();
    $item = $this->links[$this->get($queue_index)];
    if (isset($item['index'])) {
      return $item['index'];
    }
    return false;
  }

  function setDuration($duration, $index) {
    $this->load();
    $this->links[$this->get($index)]['duration'] = $duration;
    $this->save();
  }

  function setDisplayDuration($duration, $index) {
    $this->load();
    $this->links[$this->get($index)]['display_duration'] = $duration;
    $this->save();
  }

  function setCompleted($timestamp, $index) {
    $this->load();
    $this->links[$this->get($index)]['time_complete'] = $timestamp;
    $this->save();
  }

  function pruneCompleted() {
    $this->load();
    $expire = time() - 24 * 3600;
    $statuses = ['completed', 'error'];
    foreach ($this->links as $index => $link) {
      if (!isset($link['timestamp'])) {
        continue;
      }
      if (in_array($link['status'], $statuses) && $link['timestamp'] < $expire) {
        dlog("Pruning timestamp " . date('Y-m-d h:i a', $link['timestamp']));
        dlog("Expiring [Slot " . $link['id'] . "], status " . $link['status']);
        unset($this->links[$index]);
      }
    }
    $this->links = array_values($this->links);
    $this->save(true);
  }

  function setError($msg, $index) {
    $this->setStatus('error', $index);
    $this->links[$this->get($index)]['error'] = $msg;
    $this->save();
  }

}
