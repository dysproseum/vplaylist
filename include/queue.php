<?php

class Queue {

  var $path;
  var $links;

  function Queue($path) {
    $this->path = $path;
  }

  function __construct($path) {
    $this->path = $path;
    $this->links = [];
  }

  function load() {
    $data = file_get_contents($this->path);

    if ($data === false) {
      error_log("Error opening queue file: " . $this->path);
      return false;
    }
    $this->links = json_decode($data, true);
    if (json_last_error() !== 0) {
      print json_last_error_msg();
      return false;
    }
    // Set id value.
    foreach ($this->links as $index => $link) {
      $this->links[$index]['id'] = $index;
    }
    $this->save();
    return $this->links;
  }

  function save() {
    $fp = fopen($this->path, 'wb');
    if ($fp) {
      fputs($fp, json_encode($this->links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
      fputs($fp, PHP_EOL);
      fclose($fp);
    }
    else {
      error_log("Error saving queue file: " . $this->path);
      return false;
    }
    return true;
  }

  function json() {
    return json_encode($this->links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }

  function getLinks() {
    return $this->links;
  }

  function getActiveLinks() {
    $active = [];
    foreach ($this->links as $index => $link) {
      if ($link['status'] != 'completed' && $link['status'] != 'queued') {
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
    $this->links[$index]['status'] = $status;
    $this->save();
  }

  function setTitle($title, $index) {
    $this->load();
    $this->links[$index]['title'] = $title;
    $this->save();
  }

  function setTarget($target, $index) {
    $this->load();
    $this->links[$index]['target'] = $target;
    $this->save();
  }

  function setIndex($collection_index, $queue_index) {
    $this->load();
    $this->links[$queue_index]['index'] = $collection_index;
    $this->save();
  }

  function setDuration($duration, $index) {
    $this->load();
    $this->links[$index]['duration'] = $duration;
    $this->save();
  }

  function setCompleted($timestamp, $index) {
    $this->load();
    $this->links[$index]['time_complete'] = $timestamp;
    $this->save();
  }

  function pruneCompleted() {
    $this->load();
    $expire = time() - 24 * 3600;
    foreach ($this->links as $index => $link) {
      if ($link['status'] == 'completed' && $link['timestamp'] < $expire) {
        dlog("Expiring from queue: " . $link['id'] . ", " . $link['title']);
        unset($this->links[$index]);
      }
    }
    $this->save();
  }

  function setElapsed($status, $elapsed, $index) {
    $this->load();
    $this->links[$index]['metadata'][$status]['elapsed'] = $elapsed;
    $this->save();
  }

}
