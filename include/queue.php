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
    // Set new id values.
    foreach ($this->links as $index => $link) {
      if (!isset($link['id'])) {
        if (isset($this->links[$index - 1]['id'])) {
          $this->links[$index]['id'] = $this->links[$index - 1]['id'] + 1;
        }
        else {
          $this->links[$index]['id'] = $index;
        }
      }
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

  // Array indices may be incorrect after pruning.
  function get($id) {
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
    foreach ($this->links as $index => $link) {
      if (!isset($link['timestamp'])) {
        continue;
      }
      if ($link['status'] == 'completed' && $link['timestamp'] < $expire) {
        dlog("Pruning timestamp " . date('Y-m-d h:i a', $link['timestamp']));
        dlog("Expiring from queue: " . $link['id'] . ", " . $link['title']);
        unset($this->links[$index]);
      }
    }
    $this->links = array_values($this->links);
    $this->save();
  }

  function setError($msg, $index) {
    $this->setStatus('error', $index);
    $this->links[$this->get($index)]['error'] = $msg;
    $this->save();
  }

}
