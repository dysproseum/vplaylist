<?php

class Queue {

  var $path;
  var $links;

  function Queue($path) {
    $this->path = $path;
  }

  function __construct($path) {
    $this->path = $path;
  }

  function load() {
    $this->links = [];
    $data = file_get_contents($this->path);

    if ($data === false) {
      dlog("Error opening queue file: " . $this->path);
      return false;
    }
    $this->links = json_decode($data, true);
    if (json_last_error() !== 0) {
      print json_last_error_msg();
      return false;
    }
    return $this->links;
  }

  function save() {
    $fp = fopen($this->path, 'wb');
    if ($fp) {
      fputs($fp, json_encode($this->links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
      fputs($fp, PHP_EOL);
      fclose($fp);
      //chmod($p, 0777);
    }
    else {
      dlog("Error saving queue file.");
      return false;
    }
    return true;
  }

  function getLinks() {
    return $this->links;
  }

  function queueLinks() {
    $queue = [];
    foreach ($this->links as $index => $link) {
      // Pull unset items into queue.
      if (!isset($link['status'])) {
        $this->links[$index]['status'] = 'queued';
        $queue[] = $link;
      }
    }
    $this->save();
    return $queue;
  }

  function setStatus($status, $index) {
    $this->links[$index]['status'] = $status;
    $this->save();
  }

  function setTitle($title, $index) {
    $this->links[$index]['title'] = $title;
    $this->save();
  }

  function setCompleted($index) {
    unset($this->links[$index]);
    $this->save();
  }

}
