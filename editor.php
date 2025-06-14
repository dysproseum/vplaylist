<?php

require_once 'include/bootstrap.php';

if (!isset($collections)) {
	header('Location: install.php');
	exit;
}

// get player and recorder video id's
$player_id = isset($_REQUEST['player_id']) ? $_REQUEST['player_id'] : null;
$player_collection = isset($_REQUEST['player_collection']) ? $_REQUEST['player_collection'] : null;
$recorder_id = isset($_REQUEST['recorder_id']) ? $_REQUEST['recorder_id'] : null;
$recorder_collection = isset($_REQUEST['recorder_collection']) ? $_REQUEST['recorder_collection'] : null;

// also set mark out's?

// read edit jobs file.
$path = $conf['json_editor'];
$json = file_get_contents($path);
$jobs = json_decode($json, true);

// check for new edit posted.
if (isset($_REQUEST['edit'])) {
  // @todo we only need id, collection, mark in/out from each player
  $new = $_REQUEST;

  $new['timestamp'] = time();
  $new['status'] = 'new';
  $jobs[] = $new;
  $out = json_encode($jobs, JSON_PRETTY_PRINT);
  file_put_contents($path, $out);
  chmod($path, 0777);

  header('Location: /vplaylist/editor.php?' . $_SERVER['QUERY_STRING']);
  exit;
}

// prepopulate player and/or recorder.
$indexes = ['player', 'recorder'];
$items = [];
foreach ($indexes as $index) {
  if (isset($_GET[$index . '_id']) && isset($_GET[$index . '_collection'])) {
	$id = $_GET[$index . '_id'];
	$machine_name = $_GET[$index . '_collection'];
	if (!isset($collections[$machine_name]['items'][$id])) {
		$items[$id] = false;
		header("HTTP/1.0 404 Not Found");
	}
	else {
	  $items[$index] = [
		'id' => $id,
                'machine_name' => $machine_name,
		'item' => $collections[$machine_name]['items'][$id],
	  ];
	}
  }
}

?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="include/style.css">
<script type="text/javascript" src="include/util.js"></script>
<script type="text/javascript" src="include/editor.js"></script>
<script type="text/javascript" src="https://g200kg.github.io/input-knobs/input-knobs.js"></script>
<title>vplaylist | video editor</title>
</head>

<body class="editor">

  <div class="header">
	<h1><a href="/vplaylist/index.php">vplaylist</a></h1>
	<form class="search" action="/vplaylist/search.php" method="get">
	    <input type="text" maxlength="64" name="q" placeholder="search" />
	</form>
  </div>

  <div class="subnav">
    <h2>Video Editor</h2>
    <div class="subnav-right-side">
      <h4><a href="download.php">Import Status</a></h4>
    </div>
  </div>

  <form id="video-editor-form" action="/vplaylist/editor.php?<?php print $_SERVER['QUERY_STRING']; ?>" method="post">

  <input type="text" placeholder="Working title" name="working_title"/>
  <input type="hidden" name="project_id" value="<?php print $project; ?>" />
  <input type="hidden" id="player_id" name="player_id" value="<?php print $player_id; ?>" />
  <input type="hidden" id="player_collection" name="player_collection" value="<?php print $player_collection; ?>" />
  <input type="hidden" id="recorder_id" name="recorder_id" value="<?php print $recorder_id; ?>" />
  <input type="hidden" id="recorder_collection" name="recorder_collection" value="<?php print $recorder_collection; ?>" />

  <div class="video-editor">
    <?php foreach ($indexes as $index): ?>
      <div class="player <?php print $index; ?>">

        <h2 title="<?php print basename($items[$index]['item']['filename'], '.mp4'); ?>"><?php print $index; ?></h2>
	<div class="video-select">
          <!-- load video controls -->
          <select name="collection" class="load-collection" data-player="<?php print $index; ?>">
            <option>-- Select --</option>
            <?php foreach ($collections as $machine_name => $c): ?>
              <option value="<?php print $machine_name; ?>" <?php if ($machine_name == $items[$index]['machine_name']) print 'selected="selected"'; ?>><?php print $c['name']; ?></option>
            <?php endforeach; ?>
          </select>
          <?php foreach ($collections as $machine_name => $c): ?>
            <select class="load-item" id="<?php print $index; ?>-collection-<?php print $machine_name; ?>" data-player="<?php print $index; ?>" <?php if ($machine_name != $items[$index]['machine_name']) print 'style="display: none;"'; ?>>
            <option>-- Select --</option>
            <?php foreach ($c['items'] as $id => $item): ?>
              <option value="<?php print $id; ?>" <?php if ($machine_name == $items[$index]['machine_name'] && $id == $items[$index]['id']) print 'selected="selected"'; ?>><?php print $item['title']; ?> (<?php print seconds_to_clock_time($item['duration']); ?>)</option>
            <?php endforeach; ?>
            </select>
          <?php endforeach; ?>
          <!-- load video controls -->
	</div>

        <div class="player-<?php print $index; ?>">
	  <video controls id="<?php print $index; ?>">
            <source src="serve.php?collection=<?php print $items[$index]['machine_name']; ?>&index=<?php print $items[$index]['id']; ?>&file=.mp4" type="video/mp4" />
	  </video>
          <canvas id="canvas-<?php print $index; ?>" style="display: none;"></canvas>
        </div>

	<span id="vid_marks" class="label">
		<button type="button" id="<?php print $index; ?>-mark-in">Mark In</button>
		<input type="text" id="<?php print $index; ?>-mark-in-value" name="<?php print $index; ?>-mark-in-value" />
		<span id="<?php print $index; ?>-time-counter">00:00:00.00</span>
		<button type="button" id="<?php print $index; ?>-mark-out">Mark Out</button>
		<input type="text" id="<?php print $index; ?>-mark-out-value" name="<?php print $index; ?>-mark-out-value" />

		 <input id="<?php print $index; ?>-speed" type="range" class="input-knob" data-src="https://raw.githubusercontent.com/g200kg/input-knobs/refs/heads/master/images/knob70.png" data-sprites="100" min="-2" max="2" step="0.01" />

		<div class="<?php print $index; ?>-jog-wrap" style="width: 32px; height: 32px;">
		  <!--?xml version="1.0" encoding="UTF-8"?-->
		  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
		    <symbol id="circle-with-dot" viewBox="-6 -6 12 12">
		      <circle cx="0" cy="0" r="5.875" stroke="#2e3436" fill="#babdb6" stroke-width="0.25"></circle>
		      <circle cx="0" cy="-3.75" r="0.75" stroke="none" fill="#2e3436"></circle>
		    </symbol>
		    <use xlink:href="#circle-with-dot" x="0" y="0" fill="red"></use>
		  </svg>
		  <input type="range" id="<?php print $index; ?>-jog" style="display: none"/>
		</div>
	</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="control-panel">
    <button type="button" id="preview">Preview</button>
    <button type="button" id="stop">Stop</button>
    <input type="submit" id="record" value="Record" />

    <input type="checkbox" name="audio-insert" id="audio-insert" value="1" />
    <label for="audio-insert">Audio Insert</label>
    <input type="checkbox" name="video-insert" id="video-insert" value="1" />
    <label for="video-insert">Video Insert</label>

    <input type="radio" name="edit" id="edit-insert" value="insert" />
    <label for="edit-insert">Insert</label>
    <input type="radio" name="edit" id="edit-assemble" value="assemble" />
    <label for="edit-assemble">Assemble</label>

    <input type="radio" name="edit" id="edit-clip" value="clip" />
    <label for="edit-clip">Video Clip</label>
    <input type="radio" name="edit" id="edit-dub" value="dub" />
    <label for="edit-dub">Audio Dub</label>

  </div>
  </form>

  <?php if ($jobs): ?>
    <div class="jobs">
      <?php foreach($jobs as $job): ?>
        <div class="job">
          <?php print date('Y-m-d h:i:s a', $job['timestamp']); ?>
          Edit action: <?php print $job['edit']; ?>
          Status: <?php print $job['status']; ?>
          <?php if ($job['status'] == 'completed'): ?>
            <a href="<?php print $job['target']; ?>" title="<?php print $job['title']; ?>">Watch Now</a>
            <a href="/vplaylist/editor.php?collection=<?php print $job['collection']; ?>&player=<?php print $job['index']; ?>&recorder=<?php print $recorder; ?>">Load player</a>
            <a href="/vplaylist/editor.php?collection=<?php print $job['collection']; ?>&player=<?php print $player; ?>&recorder=<?php print $job['index']; ?>">Load recorder</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php require_once 'include/footer.php'; ?>
