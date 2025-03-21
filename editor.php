<?php

require_once 'include/bootstrap.php';

if (!isset($collections)) {
	header('Location: install.php');
	exit;
}

if (isset($_REQUEST['collection'])) {
	$machine_name = $_REQUEST['collection'];
}
else {
	$machine_name = '';
}

$player = isset($_REQUEST['player']) ? $_REQUEST['player'] : null;
$recorder = isset($_REQUEST['recorder']) ? $_REQUEST['recorder'] : null;

// read edit jobs file.
$path = $conf['json_editor'];
$json = file_get_contents($path);
$jobs = json_decode($json, true);

// check for new edit posted.
if (isset($_REQUEST['edit'])) {
  $new = $_REQUEST;
  $new['timestamp'] = time();
  $new['status'] = 'new';
  $jobs[] = $new;
  $out = json_encode($jobs, JSON_PRETTY_PRINT);
  file_put_contents($path, $out);
  chmod($path, 0777);

  // can we unset these or just redirect?
  foreach ($_REQUEST as $key => $value) {
      unset($_REQUEST[$key]);
  }

  header('Location: /vplaylist/editor.php?' . $_SERVER['QUERY_STRING']);
  exit;
}


$indexes = ['player', 'recorder'];
$items = [];
foreach ($indexes as $index) {
  if (isset($_REQUEST[$index]) && $machine_name != '') {
	$id = $_REQUEST[$index];
	if (!isset($collections[$machine_name]['items'][$id])) {
		$items[$id] = false;
		header("HTTP/1.0 404 Not Found");
	}
	else {
	  $items[$index] = [
		'id' => $id,
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
    Video Editor
  </div>

  <form action="/vplaylist/editor.php?<?php print $_SERVER['QUERY_STRING']; ?>" method="post">
  <div class="video-editor">
    <?php foreach ($indexes as $index): ?>
      <div class="player <?php print $index; ?>">
        <h2><?php print $index; ?></h2>
        <div class="player-<?php print $index; ?>">
	  <video controls id="<?php print $index; ?>">
            <source src="serve.php?collection=<?php print $machine_name; ?>&index=<?php print $items[$index]['id']; ?>&file=.mp4" type="video/mp4" />
	  </video>
        </div>

	<span id="vid_title" class="label">
		<?php print basename($items[$index]['item']['filename'], '.mp4'); ?>
	</span>
	<button type="button" id="<?php print $index; ?>-mark-in">Mark In</button>
	<input type="text" id="<?php print $index; ?>-mark-in-value" name="<?php print $index; ?>-mark-in-value" />

	<button type="button" id="<?php print $index; ?>-mark-out">Mark Out</button>
	<input type="text" id="<?php print $index; ?>-mark-out-value" name="<?php print $index; ?>-mark-out-value" />
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
            <a href="/vplaylist/index.php?collection=<?php print $job['collection']; ?>&index=<?php print $job['index']; ?>" title="<?php print $job['title']; ?>">Watch Now</a>
            <a href="/vplaylist/editor.php?collection=<?php print $job['collection']; ?>&player=<?php print $job['index']; ?>&recorder=<?php print $recorder; ?>">Load player</a>
            <a href="/vplaylist/editor.php?collection=<?php print $job['collection']; ?>&player=<?php print $player; ?>&recorder=<?php print $job['index']; ?>">Load recorder</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php require_once 'include/footer.php'; ?>
