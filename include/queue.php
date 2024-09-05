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
    }
    else {
      dlog("Error saving queue file.");
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

  function getNonCompletedLinks() {
    $queue = [];
    foreach ($this->links as $index => $link) {
      if ($link['status'] != 'completed') {
        $queue[] = $link;
      }
    }
    return $queue;
  }

  function queueLinks() {
    $queue = [];
    foreach ($this->links as $index => $link) {
      // Pull unset items into queue.
      if (!isset($link['status'])) {
        $this->setStatus('queued', $index);
        $queue[] = $link;
      }
      if ($link['status'] == 'queued') {
        $queue[] = $link;
      }
    }
    return $queue;
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

  function setCompleted($index) {
    unset($this->links[$index]);
    $this->save();
  }

}
